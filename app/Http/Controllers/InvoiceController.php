<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\InvoiceCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->with('customer')
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customer) use ($search) {
                            $customer->where('name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        return view('invoices.create', $this->formData());
    }

    public function store(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $this->validated($request);
        $totals = InvoiceCalculator::calculate($data['items'], (float) ($data['discount'] ?? 0));

        try {
            $invoice = DB::transaction(function () use ($data, $totals, $request, $inventory) {
                $invoice = Invoice::query()->create([
                    'invoice_number' => Invoice::nextNumber(),
                    'customer_id' => $data['customer_id'],
                    'user_id' => $request->user()->id,
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'],
                    'subtotal' => $totals['subtotal'],
                    'tax_amount' => $totals['tax_amount'],
                    'discount' => $totals['discount'],
                    'total' => $totals['total'],
                    'amount_paid' => 0,
                    'status' => $data['status_action'] === 'generate' ? 'sent' : 'draft',
                    'notes' => $data['notes'] ?? null,
                ]);

                $invoice->items()->createMany($totals['items']);
                $invoice->refreshStatus();

                if ($invoice->status !== 'draft') {
                    $inventory->deductForInvoice($invoice);
                }

                return $invoice;
            });
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $message = $invoice->status === 'draft'
            ? 'Invoice saved as draft.'
            : 'Invoice generated and stock reduced.';

        return redirect()->route('invoices.show', $invoice)->with('success', $message);
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'items.product', 'payments', 'user', 'gatewayTransactions']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        if ($invoice->status === 'cancelled' || $invoice->status === 'paid') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Paid or cancelled invoices cannot be edited.');
        }

        $invoice->load('items');

        return view('invoices.edit', array_merge($this->formData(), compact('invoice')));
    }

    public function update(Request $request, Invoice $invoice, InventoryService $inventory): RedirectResponse
    {
        if (! $invoice->isEditable()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'This invoice can no longer be edited.');
        }

        $data = $this->validated($request);
        $totals = InvoiceCalculator::calculate($data['items'], (float) ($data['discount'] ?? 0));
        $wasDraft = $invoice->status === 'draft';

        try {
            DB::transaction(function () use ($invoice, $data, $totals, $wasDraft, $inventory) {
                if ($invoice->stock_deducted) {
                    $inventory->restoreForInvoice($invoice);
                }

                $invoice->update([
                    'customer_id' => $data['customer_id'],
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'],
                    'subtotal' => $totals['subtotal'],
                    'tax_amount' => $totals['tax_amount'],
                    'discount' => $totals['discount'],
                    'total' => $totals['total'],
                    'notes' => $data['notes'] ?? null,
                    'status' => $data['status_action'] === 'generate' && $wasDraft
                        ? 'sent'
                        : $invoice->status,
                ]);

                $invoice->items()->delete();
                $invoice->items()->createMany($totals['items']);
                $invoice->refreshStatus();

                if ($invoice->status !== 'draft') {
                    $inventory->deductForInvoice($invoice->fresh('items.product'));
                }
            });
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        if ($invoice->payments()->exists()) {
            return back()->with('error', 'Invoices with payments cannot be deleted. Cancel it instead.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function markSent(Invoice $invoice, InventoryService $inventory): RedirectResponse
    {
        if ($invoice->status === 'draft') {
            try {
                $inventory->deductForInvoice($invoice);
            } catch (InsufficientStockException $exception) {
                return back()->with('error', $exception->getMessage());
            }

            $invoice->status = 'sent';
            $invoice->save();
            $invoice->refreshStatus();
        }

        return back()->with('success', 'Invoice marked as sent.');
    }

    public function cancel(Invoice $invoice, InventoryService $inventory): RedirectResponse
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Paid invoices cannot be cancelled.');
        }

        $inventory->restoreForInvoice($invoice);
        $invoice->update(['status' => 'cancelled']);

        return back()->with('success', 'Invoice cancelled.');
    }

    public function downloadPdf(Invoice $invoice): Response
    {
        $invoice->load(['customer', 'items', 'payments']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))->setPaper('a4');

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    public function printPdf(Invoice $invoice): Response
    {
        $invoice->load(['customer', 'items', 'payments']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))->setPaper('a4');

        return $pdf->stream($invoice->invoice_number.'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'customers' => Customer::query()->orderBy('company_name')->orderBy('name')->get(),
            'products' => Product::query()->active()->orderBy('name')->get()->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'unit_price' => (float) $product->selling_price,
                'tax_rate' => (float) $product->tax_rate,
                'quantity' => (float) $product->quantity,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status_action' => ['required', 'in:draft,generate'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
    }
}
