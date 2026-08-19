<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $shopTables = [
        'users',
        'categories',
        'suppliers',
        'products',
        'customers',
        'sales',
        'purchases',
        'invoices',
        'expenses',
        'payments',
        'stock_movements',
        'audit_logs',
        'settings',
        'gateway_transactions',
        'sale_items',
        'purchase_items',
        'invoice_items',
    ];

    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        $shopName = 'Chindeka Tuck Shop';
        $shopPhone = null;
        $shopAddress = null;
        if (Schema::hasTable('settings')) {
            $shopName = DB::table('settings')->where('key', 'company.name')->value('value') ?: $shopName;
            $shopPhone = DB::table('settings')->where('key', 'company.phone')->value('value');
            $shopAddress = DB::table('settings')->where('key', 'company.address')->value('value');
        }

        $needsBackfill = false;
        foreach ($this->shopTables as $tableName) {
            if (Schema::hasTable($tableName) && DB::table($tableName)->exists()) {
                $needsBackfill = true;
                break;
            }
        }

        $shopId = null;
        if ($needsBackfill) {
            $shopId = DB::table('shops')->insertGetId([
                'name' => $shopName,
                'phone' => $shopPhone,
                'address' => $shopAddress,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($this->shopTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'shop_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('shop_id')->nullable()->constrained()->restrictOnDelete();
            });

            if ($shopId) {
                DB::table($tableName)->whereNull('shop_id')->update(['shop_id' => $shopId]);
            }
        }

        $this->replaceUnique('categories', ['name'], ['shop_id', 'name']);
        $this->replaceUnique('settings', ['key'], ['shop_id', 'key']);
        $this->replaceUnique('sales', ['sale_number'], ['shop_id', 'sale_number']);
        $this->replaceUnique('purchases', ['purchase_number'], ['shop_id', 'purchase_number']);
        $this->replaceUnique('invoices', ['invoice_number'], ['shop_id', 'invoice_number']);

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'sku')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unique(['shop_id', 'sku']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            $this->dropUniqueQuietly('products', ['shop_id', 'sku']);
        }

        $this->replaceUnique('invoices', ['shop_id', 'invoice_number'], ['invoice_number']);
        $this->replaceUnique('purchases', ['shop_id', 'purchase_number'], ['purchase_number']);
        $this->replaceUnique('sales', ['shop_id', 'sale_number'], ['sale_number']);
        $this->replaceUnique('settings', ['shop_id', 'key'], ['key']);
        $this->replaceUnique('categories', ['shop_id', 'name'], ['name']);

        foreach (array_reverse($this->shopTables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'shop_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('shop_id');
            });
        }

        Schema::dropIfExists('shops');
    }

    /**
     * @param  list<string>  $from
     * @param  list<string>  $to
     */
    private function replaceUnique(string $table, array $from, array $to): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $this->dropUniqueQuietly($table, $from);

        Schema::table($table, function (Blueprint $blueprint) use ($to) {
            $blueprint->unique($to);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropUniqueQuietly(string $table, array $columns): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->dropUnique($columns);
            });
        } catch (\Throwable) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                    $blueprint->dropUnique($table.'_'.implode('_', $columns).'_unique');
                });
            } catch (\Throwable) {
                // Index may already be gone on SQLite.
            }
        }
    }
};
