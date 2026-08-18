<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action')->nullable()->after('user_id');
            $table->text('description')->nullable()->after('action');
            $table->string('ip_address')->nullable()->after('description');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable()->after('notes');
            $table->timestamp('cancel_requested_at')->nullable()->after('cancel_reason');
            $table->foreignId('cancelled_by')->nullable()->after('cancel_requested_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancel_reason', 'cancel_requested_at']);
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['action', 'description', 'ip_address']);
        });
    }
};
