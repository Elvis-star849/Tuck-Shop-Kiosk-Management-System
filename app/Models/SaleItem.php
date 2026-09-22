<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\RecordsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use BelongsToShop, RecordsChanges;

    protected $fillable = [
        'shop_id',
        'sale_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'cost_price',
        'line_total',
        'quantity_returned',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity_returned' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function pendingReturnQuantity(): float
    {
        return round((float) SaleReturnItem::query()
            ->where('sale_item_id', $this->id)
            ->whereHas('saleReturn', fn ($query) => $query->where('status', 'requested'))
            ->sum('quantity'), 2);
    }

    public function returnableQuantity(): float
    {
        return round(max(0, (float) $this->quantity - (float) $this->quantity_returned - $this->pendingReturnQuantity()), 2);
    }
}
