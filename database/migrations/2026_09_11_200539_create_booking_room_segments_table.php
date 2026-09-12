<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_room_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->dateTime('check_in_at');
            $table->dateTime('check_out_at')->nullable(); // null = гость всё ещё в этом номере
            $table->string('reason')->nullable(); // причина переселения
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['booking_id']);
            $table->index(['room_id', 'check_in_at', 'check_out_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_room_segments');
    }
};