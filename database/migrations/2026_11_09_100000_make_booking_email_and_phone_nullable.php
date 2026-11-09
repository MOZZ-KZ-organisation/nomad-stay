<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Админка: email гостя необязателен; phone обязателен только для нового гостя
     * (если выбран существующий user_id, телефон может отсутствовать и у самого пользователя).
     * Поэтому на уровне БД обе колонки должны допускать NULL.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Иначе возврат к NOT NULL упадёт на уже созданных бронях без email/телефона.
        DB::table('bookings')->whereNull('email')->update(['email' => '']);
        DB::table('bookings')->whereNull('phone')->update(['phone' => '']);

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('phone', 50)->nullable(false)->change();
        });
    }
};
