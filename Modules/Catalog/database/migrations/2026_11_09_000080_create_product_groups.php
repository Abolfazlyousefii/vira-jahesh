<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('group_id')->nullable()->constrained('product_groups')->restrictOnDelete();
            $table->string('color', 20)->default('');
            $table->string('size', 12)->default('');
            $table->unique(['group_id', 'color', 'size']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique(['group_id', 'color', 'size']);
            $table->dropForeign(['group_id']);
            $table->dropColumn(['group_id', 'color', 'size']);
        });
        Schema::dropIfExists('product_groups');
    }
};
