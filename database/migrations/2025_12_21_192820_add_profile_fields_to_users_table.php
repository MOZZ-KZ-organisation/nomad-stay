<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->unique()->after('id');
            $table->string('phone')->nullable()->after('name');
            $table->date('birth_date')->nullable()->after('phone');
            $table->string('citizenship')->nullable()->after('birth_date');
            $table->string('address')->nullable()->after('citizenship');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'login',
                'phone',
                'birth_date',
                'citizenship',
                'address',
            ]);
        });
    }
};
