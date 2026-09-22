<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\RecordsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToShop, RecordsChanges;
    public const METHODS = [
        'ecocash' => 'EcoCash',
        'paynow' => 'Paynow',
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'card' => 'Card',
        'mobile_money' => 'Mobile Money',
    ];

    public const TYPES = [
        'payment' => 'Payment',
        'refund' => 'Refund',
    ];

    protected $attributes = [
        'type' => 'payment',
    ];

    protected $fillable = [
        'shop_id',
        'invoice_id',
        'sale_id',
        'sale_return_id',
        'type',
        'amount',
        'payment_method',
        'payment_reference',
        'payment_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    public function signedAmount(): float
    {
        $amount = (float) $this->amount;

        return $this->isRefund() ? -abs($amount) : $amount;
    }
}
