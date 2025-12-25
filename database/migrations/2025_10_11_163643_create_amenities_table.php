<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('amenity_hotel', function (Blueprint $table) {
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->primary(['hotel_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenity_hotel');
        Schema::dropIfExists('amenities');
    }
};
