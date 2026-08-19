<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use BelongsToShop;
    public const TYPES = [
        'stock_in' => 'Stock In',
        'sale' => 'Sale',
        'damaged' => 'Damaged',
        'expired' => 'Expired',
        'lost' => 'Lost',
        'return' => 'Return',
        'adjustment' => 'Adjustment',
    ];

    public const OUT_REASONS = [
        'damaged' => 'Damaged',
        'expired' => 'Expired',
        'lost' => 'Lost',
        'personal_use' => 'Personal Use',
        'adjustment' => 'Stock Adjustment',
        'return_supplier' => 'Return to Supplier',
    ];

    protected $fillable = [
        'shop_id',
        'product_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'reason',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'quantity_before' => 'decimal:2',
            'quantity_after' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
