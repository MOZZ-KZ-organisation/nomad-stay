<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->index(['hotel_id', 'capacity'],'idx_rooms_hotel_capacity');
            $table->index('hotel_id','idx_rooms_hotel_id');
            $table->index('capacity', 'idx_rooms_capacity');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('idx_rooms_hotel_capacity');
            $table->dropIndex('idx_rooms_hotel_id');
            $table->dropIndex('idx_rooms_capacity');
        });
    }
};
