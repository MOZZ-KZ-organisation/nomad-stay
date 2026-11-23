<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Тарифы на период по датам: «с 1 декабря по 15 января номер стоит 25 000 за ночь».
        // Не путать с rate_rules — там доплаты за ранний заезд / поздний выезд по времени суток.
        Schema::create('period_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->string('title');
            // Обе границы включительно, по датам ночей (ночь = дата вечера заезда).
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedInteger('price'); // цена за ночь, как rooms.price
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hotel_id', 'is_active', 'date_from', 'date_to']);
        });

        // Один тариф можно назначить сразу на несколько номеров.
        Schema::create('period_rate_room', function (Blueprint $table) {
            $table->foreignId('period_rate_id')->constrained('period_rates')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');

            $table->primary(['period_rate_id', 'room_id']);
            $table->index('room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_rate_room');
        Schema::dropIfExists('period_rates');
    }
};
