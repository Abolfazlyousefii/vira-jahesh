<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_token')->unique();
            $table->string('request_hash', 64);
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('customer_name', 120);
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('channel', 20);
            $table->string('payment_method', 20);
            $table->unsignedBigInteger('total_toman');
            $table->timestamps();
            $table->index('created_at');
        });
        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('name', 160);
            $table->string('sku', 64);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_toman');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
