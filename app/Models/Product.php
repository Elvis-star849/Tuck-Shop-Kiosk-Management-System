<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'discontinued' => 'Discontinued',
    ];

    protected $fillable = [
        'sku',
        'barcode',
        'category_id',
        'name',
        'description',
        'cost_price',
        'selling_price',
        'unit_price',
        'tax_rate',
        'quantity',
        'min_stock',
        'unit',
        'supplier_id',
        'expiry_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'quantity' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'expiry_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if ($product->isDirty('selling_price')) {
                $product->unit_price = $product->selling_price;
            } elseif ($product->isDirty('unit_price') && ! $product->isDirty('selling_price')) {
                $product->selling_price = $product->unit_price;
            }
        });

        static::updating(function (Product $product): void {
            foreach (['selling_price', 'min_stock', 'cost_price', 'status'] as $field) {
                if ($product->isDirty($field)) {
                    $label = str_replace('_', ' ', $field);
                    AuditLog::record(
                        'product.'.$field.'_changed',
                        'Admin changed '.$product->name.' '.$label.' from '.$product->getOriginal($field).' to '.$product->{$field},
                        $product,
                        $field,
                        $product->getOriginal($field),
                        $product->{$field},
                    );
                }
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isLowStock(): bool
    {
        return (float) $this->quantity > 0 && (float) $this->quantity <= (float) $this->min_stock;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->quantity <= 0;
    }

    public function stockLabel(): string
    {
        if ($this->isOutOfStock()) {
            return 'Out of Stock';
        }

        if ($this->isLowStock()) {
            return 'Low Stock';
        }

        return 'Active';
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->lte(now()->startOfDay());
    }

    public function isExpiringSoon(int $days = 14): bool
    {
        if ($this->expiry_date === null || $this->isExpired()) {
            return false;
        }

        return $this->expiry_date->lte(now()->addDays($days)->endOfDay());
    }

    public function scopeExpiringSoon($query, int $days = 14)
    {
        return $query
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>', now())
            ->whereDate('expiry_date', '<=', now()->addDays($days));
    }

    public function profitPerUnit(): float
    {
        return round((float) $this->selling_price - (float) $this->cost_price, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public static function nextSku(): string
    {
        $next = ((int) static::query()->max('id')) + 1;

        do {
            $sku = 'SKU-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (static::query()->where('sku', $sku)->exists());

        return $sku;
    }
}
