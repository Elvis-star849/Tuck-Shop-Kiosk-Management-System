<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('cashier')->after('password');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('id');
            $table->string('barcode')->nullable()->after('sku');
            $table->foreignId('category_id')->nullable()->after('barcode')->constrained()->nullOnDelete();
            $table->decimal('cost_price', 12, 2)->default(0)->after('description');
            $table->decimal('selling_price', 12, 2)->default(0)->after('cost_price');
            $table->decimal('quantity', 12, 2)->default(0)->after('selling_price');
            $table->decimal('min_stock', 12, 2)->default(0)->after('quantity');
            $table->string('unit')->default('items')->after('min_stock');
            $table->foreignId('supplier_id')->nullable()->after('unit')->constrained()->nullOnDelete();
            $table->date('expiry_date')->nullable()->after('supplier_id');
            $table->string('status')->default('active')->after('expiry_date');
        });

        DB::table('products')->orderBy('id')->get()->each(function ($product) {
            DB::table('products')->where('id', $product->id)->update([
                'sku' => 'SKU-'.str_pad((string) $product->id, 4, '0', STR_PAD_LEFT),
                'selling_price' => $product->unit_price,
            ]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('stock_deducted')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('stock_deducted');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn([
                'sku', 'barcode', 'cost_price', 'selling_price', 'quantity',
                'min_stock', 'unit', 'expiry_date', 'status',
            ]);
        });
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('categories');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
