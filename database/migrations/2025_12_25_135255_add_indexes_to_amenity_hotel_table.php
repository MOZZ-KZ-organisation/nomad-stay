<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenity_hotel', function (Blueprint $table) {
            $table->index(['amenity_id', 'hotel_id'], 'idx_amenity_hotel_amenity_hotel');
        });
    }

    public function down(): void
    {
        Schema::table('amenity_hotel', function (Blueprint $table) {
            $table->dropIndex('idx_amenity_hotel_amenity_hotel');
        });
    }
};
