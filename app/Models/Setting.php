<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\RecordsChanges;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToShop, RecordsChanges;

    protected $fillable = ['shop_id', 'key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
