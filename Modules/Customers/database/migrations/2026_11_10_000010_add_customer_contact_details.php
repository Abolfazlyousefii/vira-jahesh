<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('email', 190)->nullable();
            $table->string('customer_type', 20)->default('person');
        });

        Schema::create('customer_phone_numbers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('number_normalized', 20)->index();
            $table->string('label', 60)->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'number_normalized'], 'customer_phone_unique_per_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_phone_numbers');
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn(['email', 'customer_type']));
    }
};
