<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const CATEGORIES = [
        'Transport',
        'Electricity',
        'Rent',
        'Packaging',
        'Other',
    ];

    protected $fillable = [
        'expense_date',
        'category',
        'description',
        'amount',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
