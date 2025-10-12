<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->index();
            $table->string('country')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->tinyInteger('stars')->default(0)->index();
            $table->string('type')->nullable()->index(); 
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('min_price')->nullable()->index();
            $table->timestamps();
            $table->index(['city','is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
