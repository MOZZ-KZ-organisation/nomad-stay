<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->unsignedTinyInteger('discount_percent');
            $table->unsignedInteger('price_override')->nullable();
            $table->timestamps();
            $table->unique('hotel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_discounts');
    }
};
