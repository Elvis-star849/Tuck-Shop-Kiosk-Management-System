<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\RecordsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    use BelongsToShop, RecordsChanges;

    public const STATUSES = [
        'requested' => 'Requested',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected $fillable = [
        'shop_id',
        'sale_id',
        'return_number',
        'status',
        'refund_amount',
        'refund_method',
        'reason',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'id', 'sale_return_id');
    }

    public function isRequested(): bool
    {
        return $this->status === 'requested';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function scopeRequested($query)
    {
        return $query->where('status', 'requested');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public static function nextNumber(): string
    {
        $last = static::query()->orderByDesc('id')->value('return_number');
        $next = $last ? ((int) substr((string) $last, -5)) + 1 : 1;

        return 'RET-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
