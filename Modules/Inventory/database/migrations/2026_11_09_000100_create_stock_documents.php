<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 20)->nullable();
            $table->timestamps();
        });
        Schema::create('stock_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_token')->unique();
            $table->string('request_hash', 64);
            $table->string('kind', 12);
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('target_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('supplier_name', 120)->nullable();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 160);
            $table->string('sku', 64);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_cost_toman')->nullable();
            $table->unsignedBigInteger('total_toman')->nullable();
            $table->string('reason', 255);
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_documents');
        Schema::dropIfExists('suppliers');
    }
};
