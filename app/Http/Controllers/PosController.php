<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Services\InventoryService;
use App\Services\PaynowEcocashGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class PosController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'selling_price', 'quantity', 'unit', 'min_stock']);

        return view('pos.index', compact('products'));
    }

    public function store(Request $request, InventoryService $inventory, PaynowEcocashGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,ecocash,paynow,card'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'phone' => ['required_if:payment_method,ecocash', 'nullable', 'string', 'min:9', 'max:20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $gatewayMethod = in_array($data['payment_method'], ['ecocash', 'paynow'], true);

        try {
            $sale = DB::transaction(function () use ($data, $inventory, $request, $gatewayMethod) {
                $subtotal = 0;
                $lines = [];

                foreach ($data['items'] as $row) {
                    $product = Product::query()->lockForUpdate()->findOrFail($row['product_id']);
                    $qty = (float) $row['quantity'];
                    if ($qty > (float) $product->quantity) {
                        throw new InsufficientStockException(
                            'Insufficient stock. Only '.$product->quantity.' '.$product->name.' available.'
                        );
                    }

                    $lineTotal = round($qty * (float) $product->selling_price, 2);
                    $subtotal += $lineTotal;
                    $lines[] = compact('product', 'qty', 'lineTotal');
                }

                $discount = round((float) ($data['discount'] ?? 0), 2);
                $total = max(0, round($subtotal - $discount, 2));
                $amountPaid = $gatewayMethod ? 0.0 : round((float) ($data['amount_paid'] ?? 0), 2);
                if (! $gatewayMethod && $amountPaid + 0.009 < $total) {
                    throw new \RuntimeException('Amount paid is less than the total.');
                }

                $sale = Sale::query()->create([
                    'sale_number' => Sale::nextNumber(),
                    'user_id' => $request->user()->id,
                    'sold_at' => now(),
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'amount_paid' => $amountPaid,
                    'change_due' => $gatewayMethod ? 0 : round($amountPaid - $total, 2),
                    'payment_method' => $data['payment_method'],
                    'status' => $gatewayMethod ? 'pending_payment' : 'completed',
                ]);

                foreach ($lines as $line) {
                    $sale->items()->create([
                        'product_id' => $line['product']->id,
                        'description' => $line['product']->name,
                        'quantity' => $line['qty'],
                        'unit_price' => $line['product']->selling_price,
                        'cost_price' => $line['product']->cost_price,
                        'line_total' => $line['lineTotal'],
                    ]);

                    $inventory->apply(
                        $line['product'],
                        'sale',
                        $line['qty'],
                        'POS sale',
                        $sale->sale_number,
                        Sale::class,
                        $sale->id,
                    );
                }

                if (! $gatewayMethod) {
                    Payment::query()->create([
                        'sale_id' => $sale->id,
                        'invoice_id' => null,
                        'amount' => $total,
                        'payment_method' => $data['payment_method'],
                        'payment_reference' => $sale->sale_number,
                        'payment_date' => now()->toDateString(),
                        'notes' => 'POS '.$sale->sale_number,
                    ]);
                }

                return $sale;
            });
        } catch (InsufficientStockException|\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        if ($gatewayMethod) {
            AuditLog::record(
                'sale.pending_payment',
                $request->user()->name.' started '.$sale->sale_number.' via '.$data['payment_method'],
                $sale,
            );

            try {
                if ($data['payment_method'] === 'ecocash') {
                    $phone = $gateway->normalizePhone((string) $data['phone']);
                    if (! preg_match('/^07[78]\d{7}$/', $phone)) {
                        return redirect()->route('sales.show', $sale)
                            ->with('error', 'Enter a valid EcoCash number starting with 077 or 078.');
                    }

                    $transaction = $gateway->initiate($sale, (float) $sale->total, 'ecocash', $phone);
                } else {
                    $transaction = $gateway->initiate($sale, (float) $sale->total, 'paynow');
                }
            } catch (Throwable $exception) {
                return redirect()->route('sales.show', $sale)->with('error', $exception->getMessage());
            }

            return app(EcocashPaymentController::class)->afterInitiate($transaction, $gateway);
        }

        AuditLog::record(
            'sale.completed',
            $request->user()->name.' completed '.$sale->sale_number.' for '.money($sale->total),
            $sale,
        );

        return redirect()->route('sales.receipt', $sale)->with('success', 'Sale completed.');
    }
}
