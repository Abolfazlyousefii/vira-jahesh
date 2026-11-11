<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_sku_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 100)->unique();
            $table->string('prefix', 3)->nullable()->unique();
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_sku_sequences');
    }
};
