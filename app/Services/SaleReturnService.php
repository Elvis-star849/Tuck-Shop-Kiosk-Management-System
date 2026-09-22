<?php

namespace App\Services;

use App\Exceptions\SaleReturnException;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaleReturnService
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function normalizeReceiptNumber(string $number): string
    {
        $number = strtoupper(trim($number));
        $number = preg_replace('/\s+/', '', $number) ?? $number;

        if ($number === '') {
            return '';
        }

        if (! str_starts_with($number, 'SALE-')) {
            $digits = preg_replace('/\D+/', '', $number) ?: $number;
            $number = 'SALE-'.str_pad($digits, 5, '0', STR_PAD_LEFT);
        }

        return $number;
    }

    public function findSale(string $number): Sale
    {
        $saleNumber = $this->normalizeReceiptNumber($number);
        if ($saleNumber === '' || $saleNumber === 'SALE-00000') {
            throw new SaleReturnException('Enter a receipt number such as SALE-00016.');
        }

        $sale = Sale::query()->where('sale_number', $saleNumber)->first();
        if (! $sale) {
            throw new SaleReturnException('No receipt found for '.$saleNumber.'.');
        }

        $this->assertReturnable($sale);

        return $sale->load(['items.product', 'user', 'returns.items']);
    }

    public function assertReturnable(Sale $sale): void
    {
        if ($sale->isPendingPayment()) {
            throw new SaleReturnException('This sale is still waiting for payment and cannot be returned.');
        }

        if ($sale->isCancelled()) {
            throw new SaleReturnException('This sale was cancelled and cannot be returned.');
        }

        if ($sale->isRefunded()) {
            throw new SaleReturnException('This sale has already been fully refunded.');
        }

        if (! $sale->isCompleted() && ! $sale->isCancelRequested()) {
            throw new SaleReturnException('Only a completed sale can be returned.');
        }

        if ($sale->isCancelRequested()) {
            throw new SaleReturnException('This sale has a cancellation request. Resolve that before returning items.');
        }
    }

    /**
     * @param  array<int, array{sale_item_id: int, quantity: float|int|string}>  $rows
     */
    public function create(Sale $sale, array $rows, string $reason, User $user): SaleReturn
    {
        return DB::transaction(function () use ($sale, $rows, $reason, $user) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $sale->load(['items']);
            $this->assertReturnable($sale);

            $lines = $this->validatedLines($sale, $rows);
            $refundAmount = $this->refundAmount($sale, $lines);

            $immediate = $this->canCompleteImmediately($sale, $refundAmount, $user);
            $status = $immediate ? 'approved' : 'requested';

            $return = SaleReturn::query()->create([
                'sale_id' => $sale->id,
                'return_number' => SaleReturn::nextNumber(),
                'status' => $status,
                'refund_amount' => $refundAmount,
                'refund_method' => $sale->payment_method,
                'reason' => $reason,
                'requested_by' => $user->id,
                'approved_by' => $immediate ? $user->id : null,
                'approved_at' => $immediate ? now() : null,
            ]);

            foreach ($lines as $line) {
                $return->items()->create([
                    'sale_item_id' => $line['item']->id,
                    'quantity' => $line['qty'],
                    'line_total' => $line['line_total'],
                ]);
            }

            if ($immediate) {
                $this->fulfill($return->fresh(['items.saleItem.product', 'sale.items']), $user);
                AuditLog::record(
                    'sale_return.completed',
                    $user->name.' refunded '.$return->return_number.' on '.$sale->sale_number.' for '.money($refundAmount),
                    $return,
                );
            } else {
                AuditLog::record(
                    'sale_return.requested',
                    $user->name.' requested '.$return->return_number.' on '.$sale->sale_number.' for '.money($refundAmount),
                    $return,
                );
            }

            return $return->fresh(['items.saleItem', 'sale']);
        });
    }

    public function approve(SaleReturn $return, User $admin): SaleReturn
    {
        abort_unless($admin->isAdmin(), 403);

        return DB::transaction(function () use ($return, $admin) {
            $return = SaleReturn::query()->lockForUpdate()->findOrFail($return->id);
            if (! $return->isRequested()) {
                throw new SaleReturnException('This return is not waiting for approval.');
            }

            $sale = Sale::query()->lockForUpdate()->findOrFail($return->sale_id);
            $this->assertReturnable($sale);

            $return->load(['items.saleItem.product', 'sale.items']);
            $this->assertLinesStillAvailable($return);

            $return->update([
                'status' => 'approved',
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            $this->fulfill($return->fresh(['items.saleItem.product', 'sale.items']), $admin);

            AuditLog::record(
                'sale_return.approved',
                $admin->name.' approved '.$return->return_number.' and refunded '.money((float) $return->refund_amount),
                $return,
            );

            return $return->fresh(['items.saleItem', 'sale']);
        });
    }

    public function reject(SaleReturn $return, User $admin): SaleReturn
    {
        abort_unless($admin->isAdmin(), 403);

        if (! $return->isRequested()) {
            throw new SaleReturnException('This return is not waiting for approval.');
        }

        $return->update(['status' => 'rejected']);

        AuditLog::record(
            'sale_return.rejected',
            $admin->name.' rejected '.$return->return_number,
            $return,
        );

        return $return->fresh();
    }

    public function canCompleteImmediately(Sale $sale, float $refundAmount, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($sale->payment_method !== 'cash') {
            return false;
        }

        if (! $sale->sold_at?->isToday()) {
            return false;
        }

        $max = Setting::get('refunds.cashier_max');
        if ($max !== null && $max !== '' && $refundAmount - 0.009 > (float) $max) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, array{sale_item_id: int, quantity: float|int|string}>  $rows
     * @return list<array{item: SaleItem, qty: float, line_total: float}>
     */
    private function validatedLines(Sale $sale, array $rows): array
    {
        $items = $sale->items->keyBy('id');
        $lines = [];

        foreach ($rows as $row) {
            $id = (int) ($row['sale_item_id'] ?? 0);
            $qty = round((float) ($row['quantity'] ?? 0), 2);
            if ($id < 1 || $qty <= 0) {
                continue;
            }

            $item = $items->get($id);
            if (! $item) {
                throw new SaleReturnException('An item is not on this receipt.');
            }

            $available = $item->returnableQuantity();
            if ($qty - 0.009 > $available) {
                throw new SaleReturnException(
                    'You can only return '.$this->formatQty($available).' of '.$item->description.' from this receipt.'
                );
            }

            $lines[] = [
                'item' => $item,
                'qty' => $qty,
                'line_total' => 0.0,
            ];
        }

        if ($lines === []) {
            throw new SaleReturnException('Choose at least one item and quantity to return.');
        }

        return $this->allocateDiscount($sale, $lines);
    }

    /**
     * @param  list<array{item: SaleItem, qty: float, line_total: float}>  $lines
     * @return list<array{item: SaleItem, qty: float, line_total: float}>
     */
    private function allocateDiscount(Sale $sale, array $lines): array
    {
        $subtotal = (float) $sale->subtotal;
        $discount = (float) $sale->discount;
        $gross = 0.0;
        foreach ($lines as $line) {
            $gross += $line['qty'] * (float) $line['item']->unit_price;
        }
        $gross = round($gross, 2);
        $share = $subtotal > 0 ? round($gross * ($discount / $subtotal), 2) : 0.0;
        $refund = round(max(0, $gross - $share), 2);
        $remaining = $sale->remainingRefundableTotal();
        if ($refund > $remaining) {
            $refund = $remaining;
        }

        $assigned = 0.0;
        $last = count($lines) - 1;
        foreach ($lines as $index => &$line) {
            $lineGross = round($line['qty'] * (float) $line['item']->unit_price, 2);
            if ($index === $last) {
                $line['line_total'] = round($refund - $assigned, 2);
            } else {
                $portion = $gross > 0 ? round($refund * ($lineGross / $gross), 2) : 0.0;
                $line['line_total'] = $portion;
                $assigned += $portion;
            }
        }
        unset($line);

        return $lines;
    }

    /**
     * @param  list<array{item: SaleItem, qty: float, line_total: float}>  $lines
     */
    private function refundAmount(Sale $sale, array $lines): float
    {
        return round(array_sum(array_column($lines, 'line_total')), 2);
    }

    private function assertLinesStillAvailable(SaleReturn $return): void
    {
        foreach ($return->items as $line) {
            $item = $line->saleItem;
            if (! $item) {
                throw new SaleReturnException('A returned item is missing from the original sale.');
            }

            $available = round((float) $item->quantity - (float) $item->quantity_returned, 2);
            if ((float) $line->quantity - 0.009 > $available) {
                throw new SaleReturnException(
                    'Not enough of '.$item->description.' is left to return on this receipt.'
                );
            }
        }
    }

    private function fulfill(SaleReturn $return, User $user): void
    {
        $sale = $return->sale;
        $sale->loadMissing('items');

        foreach ($return->items as $line) {
            $item = SaleItem::query()->lockForUpdate()->findOrFail($line->sale_item_id);
            $qty = (float) $line->quantity;
            $item->update([
                'quantity_returned' => round((float) $item->quantity_returned + $qty, 2),
            ]);

            if ($item->product_id) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $this->inventory->apply(
                    $product,
                    'return',
                    $qty,
                    'Customer return',
                    $return->return_number,
                    SaleReturn::class,
                    $return->id,
                    $user->id,
                );
            }
        }

        Payment::query()->create([
            'sale_id' => $sale->id,
            'sale_return_id' => $return->id,
            'invoice_id' => null,
            'type' => 'refund',
            'amount' => (float) $return->refund_amount,
            'payment_method' => $return->refund_method,
            'payment_reference' => $return->return_number,
            'payment_date' => now()->toDateString(),
            'notes' => 'Refund '.$return->return_number.' for '.$sale->sale_number,
        ]);

        $sale->refresh()->load('items');
        if ($sale->isFullyReturned()) {
            $sale->update(['status' => 'refunded']);
        }
    }

    private function formatQty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') ?: '0';
    }
}
