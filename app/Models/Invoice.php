<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const STATUSES = [
        'draft',
        'sent',
        'partially_paid',
        'paid',
        'overdue',
        'cancelled',
    ];

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'user_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount',
        'total',
        'amount_paid',
        'status',
        'stock_deducted',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'stock_deducted' => 'boolean',
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
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function gatewayTransactions(): HasMany
    {
        return $this->hasMany(GatewayTransaction::class);
    }

    public function balance(): float
    {
        return round(max(0, (float) $this->total - (float) $this->amount_paid), 2);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'overdue', 'partially_paid'], true);
    }

    public static function nextNumber(): string
    {
        $year = now()->year;
        $prefix = 'INV-'.$year.'-';

        $last = static::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function syncAmountPaid(): void
    {
        $this->amount_paid = round((float) $this->payments()->sum('amount'), 2);
        $this->save();
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        if ($this->status === 'draft' && (float) $this->amount_paid <= 0) {
            return;
        }

        $balance = $this->balance();

        if ((float) $this->amount_paid <= 0) {
            $this->status = $this->due_date->isPast() ? 'overdue' : 'sent';
        } elseif ($balance <= 0.009) {
            $this->status = 'paid';
        } else {
            $this->status = $this->due_date->isPast() ? 'overdue' : 'partially_paid';
        }

        $this->save();
    }
}
