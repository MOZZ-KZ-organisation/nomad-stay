<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_chat_id')
                ->constrained('booking_chats')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_type')
                ->default('user');
            $table->text('body');
            $table->boolean('read')
                ->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_messages');
    }
};
