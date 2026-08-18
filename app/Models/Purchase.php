<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'user_id',
        'purchase_date',
        'reference',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public static function nextNumber(): string
    {
        $last = static::query()->orderByDesc('id')->value('purchase_number');
        $next = $last ? ((int) substr($last, -5)) + 1 : 1;

        return 'PUR-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
