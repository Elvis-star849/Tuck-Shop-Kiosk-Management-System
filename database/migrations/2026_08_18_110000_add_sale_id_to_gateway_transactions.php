<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateway_transactions', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->after('invoice_id')->constrained()->nullOnDelete();
        });

        Schema::table('gateway_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gateway_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
            $table->unsignedBigInteger('invoice_id')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
        });
    }
};
