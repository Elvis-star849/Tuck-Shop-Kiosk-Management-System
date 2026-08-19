<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToShop;
    public const METHODS = [
        'ecocash' => 'EcoCash',
        'paynow' => 'Paynow',
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'card' => 'Card',
        'mobile_money' => 'Mobile Money',
    ];

    protected $fillable = [
        'shop_id',
        'invoice_id',
        'sale_id',
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
}
