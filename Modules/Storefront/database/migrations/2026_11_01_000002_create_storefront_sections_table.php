<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key', 80)->unique();
            $table->string('type', 80);
            $table->json('settings');
            $table->unsignedSmallInteger('position');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_sections');
    }
};
