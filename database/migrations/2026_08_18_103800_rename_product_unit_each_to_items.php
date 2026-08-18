<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->where('unit', 'each')->update(['unit' => 'items']);
    }

    public function down(): void
    {
        DB::table('products')->where('unit', 'items')->update(['unit' => 'each']);
    }
};
