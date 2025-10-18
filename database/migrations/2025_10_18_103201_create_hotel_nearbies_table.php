<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_nearbies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->string('metro')->nullable()->comment('Ближайшее метро');
            $table->string('station')->nullable()->comment('Ближайшая станция');
            $table->string('park')->nullable()->comment('Ближайший парк');
            $table->string('airport')->nullable()->comment('Ближайший аэропорт');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_nearbies');
    }
};
