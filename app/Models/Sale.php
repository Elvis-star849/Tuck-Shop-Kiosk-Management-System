<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id',
        'sale_number',
        'customer_id',
        'user_id',
        'sold_at',
        'subtotal',
        'discount',
        'total',
        'amount_paid',
        'change_due',
        'payment_method',
        'status',
        'notes',
        'cancel_reason',
        'cancel_requested_at',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_due' => 'decimal:2',
            'cancel_requested_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function gatewayTransactions(): HasMany
    {
        return $this->hasMany(GatewayTransaction::class);
    }

    public function profit(): float
    {
        return round((float) $this->items->sum(function (SaleItem $item) {
            return ((float) $item->unit_price - (float) $item->cost_price) * (float) $item->quantity;
        }) - (float) $this->discount, 2);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function isCancelRequested(): bool
    {
        return $this->status === 'cancel_requested';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function balance(): float
    {
        $recorded = $this->relationLoaded('payments')
            ? (float) $this->payments->sum('amount')
            : (float) $this->payments()->sum('amount');

        $paid = max((float) $this->amount_paid, $recorded);

        return round(max(0, (float) $this->total - $paid), 2);
    }

    public function scopeCounted($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'pending_payment']);
    }

    public static function nextNumber(): string
    {
        $last = static::query()->orderByDesc('id')->value('sale_number');
        $next = $last ? ((int) substr($last, -5)) + 1 : 1;

        return 'SALE-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
