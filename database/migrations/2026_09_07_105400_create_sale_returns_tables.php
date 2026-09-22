<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->string('return_number');
            $table->string('status')->default('requested');
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('refund_method');
            $table->text('reason');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'return_number']);
            $table->index(['shop_id', 'status']);
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('quantity_returned', 12, 2)->default(0)->after('line_total');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('type')->default('payment')->after('sale_id');
            $table->foreignId('sale_return_id')->nullable()->after('type')->constrained('sale_returns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_return_id');
            $table->dropColumn('type');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('quantity_returned');
        });
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};
