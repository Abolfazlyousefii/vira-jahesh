<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', fn (Blueprint $table) => $table->index(['customer_id', 'created_at'], 'sales_customer_date_idx'));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', fn (Blueprint $table) => $table->dropIndex('sales_customer_date_idx'));
        }
    }
};
