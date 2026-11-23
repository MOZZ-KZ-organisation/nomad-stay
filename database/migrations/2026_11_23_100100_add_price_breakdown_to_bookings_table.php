<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Снимок посуточной расшифровки проживания на момент расчёта:
            // по какой цене и по какому тарифу посчитана каждая ночь.
            // Благодаря ему правка или удаление тарифа не меняет уже созданные брони.
            // У старых броней null — для них расшифровка собирается из price_for_period.
            $table->json('price_breakdown')->nullable()->after('price_for_period');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('price_breakdown');
        });
    }
};
