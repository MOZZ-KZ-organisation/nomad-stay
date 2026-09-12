<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->unsignedInteger('cancellation_fee')->default(0)->after('min_price');
            $table->time('standard_check_in_time')->default('14:00:00')->after('cancellation_fee');
            $table->time('standard_check_out_time')->default('12:00:00')->after('standard_check_in_time');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['cancellation_fee', 'standard_check_in_time', 'standard_check_out_time']);
        });
    }
};
