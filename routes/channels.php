<?php

use App\Models\BookingChat;
use App\Models\SupportChat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('booking-chat.{chatId}', function ($user, $chatId) {
    return BookingChat::where('id', $chatId)
        ->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereHas('hotel');
        })
        ->exists();
});
Broadcast::channel('support-chat.{chatId}', function ($user, $chatId) {
    if (
        SupportChat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->exists()
    ) {
        return true;
    }
    if ($user->role->name == 'admin') {
        return true;
    }
    return false;
});
