<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'card', 'transfer', 'online', 'other'])->default('cash');
            $table->dateTime('paid_at');
            $table->enum('status', ['pending', 'completed', 'refunded', 'failed'])->default('completed');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // сотрудник, принявший оплату
            $table->string('operation_number')->nullable(); // номер операции / чека
            $table->string('comment')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};