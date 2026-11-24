<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Отель, которому адресовано уведомление: менеджеры видят только свои.
            $table->unsignedBigInteger('hotel_id')->nullable()->after('booking_id')->index();
        });

        // Старые уведомления привязываем к отелю через бронь.
        DB::statement('UPDATE notifications n JOIN bookings b ON b.id = n.booking_id SET n.hotel_id = b.hotel_id WHERE n.hotel_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['hotel_id']);
            $table->dropColumn('hotel_id');
        });
    }
};
