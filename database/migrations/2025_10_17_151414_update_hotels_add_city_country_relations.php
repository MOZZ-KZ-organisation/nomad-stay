<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            // Удаляем старые текстовые поля
            if (Schema::hasColumn('hotels', 'city')) {
                $table->dropIndex(['city']); 
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('hotels', 'country')) {
                $table->dropIndex(['country']); 
                $table->dropColumn('country');
            }
            // Добавляем связи
            $table->foreignId('city_id')->nullable()
                ->after('country_id')
                ->constrained('cities')
                ->nullOnDelete();
            // Индексы на важные поля
            $table->index(['city_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropIndex(['city_id', 'is_active']);
            $table->string('city')->nullable()->index();
            $table->string('country')->nullable()->index();
        });
    }
};
