<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use BelongsToShop, HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'company_name',
        'email',
        'phone',
        'address',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function displayName(): string
    {
        return $this->company_name ?: $this->name;
    }
}
