<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\RecordsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToShop, RecordsChanges;

    protected $fillable = ['shop_id', 'name'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
