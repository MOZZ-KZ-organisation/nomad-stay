<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('guests')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'hotel_id']); // чтобы не дублировать
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
