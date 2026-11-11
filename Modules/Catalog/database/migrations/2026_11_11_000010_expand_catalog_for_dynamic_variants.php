<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_groups', function (Blueprint $table): void {
            $table->string('sku', 64)->nullable()->unique();
            $table->string('unit', 30)->nullable();
            $table->string('brand', 80)->nullable();
            $table->json('attributes')->nullable();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->string('unit', 30)->nullable();
            $table->string('brand', 80)->nullable();
            $table->json('options')->nullable();
            // Compatibility: old variants keep their historical color/size values;
            // arbitrary variants are now differentiated by their unique numeric SKU.
            $table->dropUnique(['group_id', 'color', 'size']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['unit', 'brand', 'options']);
        });
        Schema::table('product_groups', function (Blueprint $table): void {
            $table->dropColumn(['sku', 'unit', 'brand', 'attributes']);
        });
        // Do not recreate the old (group_id,color,size) unique constraint:
        // arbitrary attributes can legitimately share empty color and size.
    }
};
