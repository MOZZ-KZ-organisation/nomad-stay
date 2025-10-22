<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('tax', 10, 2)->default(0)->after('price_for_period');
            $table->decimal('total_price', 10, 2)->default(0)->after('tax');
            $table->string('first_name')->after('user_id');
            $table->string('last_name')->after('first_name');
            $table->string('email')->after('last_name');
            $table->string('country')->after('email');
            $table->string('phone', 50)->after('country');
            $table->boolean('is_business_trip')->default(false)->after('phone');
            $table->text('special_requests')->nullable()->after('is_business_trip');
            $table->time('arrival_time')->nullable()->after('special_requests');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'email',
                'country',
                'phone',
                'is_business_trip',
                'special_requests',
                'arrival_time',
            ]);
        });
    }
};
