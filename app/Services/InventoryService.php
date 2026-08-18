<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public const INBOUND = ['stock_in', 'return'];

    public function apply(
        Product $product,
        string $type,
        float $quantity,
        ?string $reason = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
    ): StockMovement {
        $quantity = abs($quantity);
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $inbound = in_array($type, self::INBOUND, true);
        $delta = $inbound ? $quantity : -$quantity;

        return DB::transaction(function () use ($product, $type, $quantity, $delta, $inbound, $reason, $notes, $referenceType, $referenceId, $userId) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $before = (float) $locked->quantity;
            $after = round($before + $delta, 2);

            if ($after < 0) {
                throw new InsufficientStockException(
                    'Insufficient stock for '.$locked->name.'. Only '.$before.' '.$locked->unit.' available.'
                );
            }

            $locked->update(['quantity' => $after]);

            return StockMovement::query()->create([
                'product_id' => $locked->id,
                'type' => $type,
                'quantity' => $inbound ? $quantity : -$quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason,
                'notes' => $notes,
                'user_id' => $userId ?? Auth::id(),
            ]);
        });
    }

    public function deductForInvoice(\App\Models\Invoice $invoice): void
    {
        if ($invoice->stock_deducted) {
            return;
        }

        $invoice->loadMissing('items.product');

        foreach ($invoice->items as $item) {
            if (! $item->product) {
                continue;
            }

            $this->apply(
                $item->product,
                'sale',
                (float) $item->quantity,
                'Invoice sale',
                $invoice->invoice_number,
                \App\Models\Invoice::class,
                $invoice->id,
            );
        }

        $invoice->update(['stock_deducted' => true]);
    }

    public function restoreForInvoice(\App\Models\Invoice $invoice): void
    {
        if (! $invoice->stock_deducted) {
            return;
        }

        $invoice->loadMissing('items.product');

        foreach ($invoice->items as $item) {
            if (! $item->product) {
                continue;
            }

            $this->apply(
                $item->product,
                'return',
                (float) $item->quantity,
                'Invoice cancelled',
                $invoice->invoice_number,
                \App\Models\Invoice::class,
                $invoice->id,
            );
        }

        $invoice->update(['stock_deducted' => false]);
    }

    public function adjustTo(Product $product, float $actual, string $reason, ?string $notes = null): StockMovement
    {
        $locked = Product::query()->findOrFail($product->id);
        $before = (float) $locked->quantity;
        $diff = round($actual - $before, 2);

        if ($diff == 0.0) {
            throw new \InvalidArgumentException('Actual quantity is the same as system quantity.');
        }

        $type = $diff > 0 ? 'stock_in' : 'adjustment';

        return $this->apply($locked, $type, abs($diff), $reason, $notes);
    }

    public function restoreForSale(\App\Models\Sale $sale): void
    {
        $sale->loadMissing('items.product');

        foreach ($sale->items as $item) {
            if (! $item->product) {
                continue;
            }

            $this->apply(
                $item->product,
                'return',
                (float) $item->quantity,
                'Sale cancelled',
                $sale->sale_number,
                \App\Models\Sale::class,
                $sale->id,
            );
        }
    }
}
