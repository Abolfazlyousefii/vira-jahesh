<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', fn (Blueprint $table) => $table->unsignedInteger('returned_quantity')->default(0));
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_token')->unique();
            $table->string('request_hash', 64);
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('refund_toman');
            $table->string('refund_method', 8);
            $table->string('reason', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
        Schema::table('sale_items', fn (Blueprint $table) => $table->dropColumn('returned_quantity'));
    }
};
