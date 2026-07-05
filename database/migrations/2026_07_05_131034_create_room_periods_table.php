<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_periods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            // free | cleaning | maintenance
            $table->enum('status', ['free', 'cleaning', 'maintenance']);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_periods');
    }
};