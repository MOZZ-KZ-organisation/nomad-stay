<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('restrict');
            $table->unsignedInteger('quantity')->default(1);
            // Цена на момент добавления — сохраняем отдельно от services.price,
            // чтобы изменение цены в справочнике не меняло уже созданные брони.
            $table->unsignedInteger('price');
            $table->unsignedInteger('amount'); // quantity * price
            $table->string('comment')->nullable();
            $table->timestamps();

            $table->index(['booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_services');
    }
};