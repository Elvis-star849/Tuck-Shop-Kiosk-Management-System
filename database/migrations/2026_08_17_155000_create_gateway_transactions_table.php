<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway')->default('paynow');
            $table->string('method')->default('ecocash');
            $table->decimal('amount', 12, 2);
            $table->string('phone');
            $table->string('reference')->unique();
            $table->string('gateway_reference')->nullable();
            $table->string('status')->default('pending');
            $table->text('poll_url')->nullable();
            $table->text('instructions')->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_transactions');
    }
};
