<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->enum('type', ['night', 'half_day', 'early_check_in', 'late_check_out']);
            // Временное окно, к которому применяется правило (для early_check_in/late_check_out).
            // Пример: early_check_in c 00:00 до 09:00 — value=100 (%), с 09:00 до 14:00 — value=50 (%).
            $table->time('from_time')->nullable();
            $table->time('to_time')->nullable();
            $table->enum('calc_type', ['fixed', 'percent'])->default('percent');
            $table->decimal('value', 10, 2); // фикс. сумма ИЛИ % от суточного тарифа номера
            $table->string('currency', 3)->default('KZT');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hotel_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_rules');
    }
};