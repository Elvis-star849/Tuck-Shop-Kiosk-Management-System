<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayTransaction extends Model
{
    public const STATUSES = ['pending', 'paid', 'cancelled', 'failed'];

    protected $fillable = [
        'invoice_id',
        'sale_id',
        'payment_id',
        'gateway',
        'method',
        'amount',
        'phone',
        'reference',
        'gateway_reference',
        'status',
        'poll_url',
        'instructions',
        'error_message',
        'payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
            'paid_at' => 'datetime',
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaynowWeb(): bool
    {
        return $this->method === 'paynow';
    }

    public function redirectUrl(): ?string
    {
        $url = $this->payload['redirect_url'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    public function payableLabel(): string
    {
        return $this->sale?->sale_number
            ?: $this->invoice?->invoice_number
            ?: $this->reference;
    }

    public function payableShowRoute(): string
    {
        if ($this->sale_id) {
            return route('sales.show', $this->sale_id);
        }

        return route('invoices.show', $this->invoice_id);
    }

    public function paidRedirectRoute(): string
    {
        if ($this->sale_id) {
            return route('sales.receipt', $this->sale_id);
        }

        return route('invoices.show', $this->invoice_id);
    }
}
