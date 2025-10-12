<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('capacity')->default(1);
            $table->unsignedTinyInteger('beds')->default(1);
            $table->unsignedTinyInteger('bathrooms')->default(1);
            $table->unsignedInteger('price'); // per night in smallest unit
            $table->unsignedInteger('stock')->default(1);
            $table->timestamps();

            $table->index(['hotel_id','price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
