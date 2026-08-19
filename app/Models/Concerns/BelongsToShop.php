<?php

namespace App\Models\Concerns;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToShop
{
    public static function bootBelongsToShop(): void
    {
        static::addGlobalScope('shop', function (Builder $query) {
            if (! Auth::hasUser()) {
                return;
            }

            $shopId = Auth::user()->shop_id;
            if (! $shopId) {
                return;
            }

            $query->where($query->getModel()->getTable().'.shop_id', $shopId);
        });

        static::creating(function (Model $model) {
            if (! empty($model->shop_id)) {
                return;
            }

            if (Auth::hasUser() && Auth::user()->shop_id) {
                $model->shop_id = Auth::user()->shop_id;

                return;
            }

            $model->shop_id = static::inferShopId($model);
        });
    }

    protected static function inferShopId(Model $model): ?int
    {
        $map = [
            'sale_id' => Sale::class,
            'invoice_id' => Invoice::class,
            'purchase_id' => Purchase::class,
            'product_id' => Product::class,
        ];

        foreach ($map as $fk => $class) {
            if (empty($model->{$fk})) {
                continue;
            }

            $related = $class::withoutGlobalScopes()->find($model->{$fk});
            if ($related?->shop_id) {
                return (int) $related->shop_id;
            }
        }

        return null;
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
