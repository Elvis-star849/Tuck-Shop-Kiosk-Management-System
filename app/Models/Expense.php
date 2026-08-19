<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToShop;
    public const CATEGORIES = [
        'Transport',
        'Electricity',
        'Rent',
        'Packaging',
        'Other',
    ];

    protected $fillable = [
        'shop_id',
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
