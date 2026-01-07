<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingMessage extends Model
{
    protected $fillable = [
        'booking_chat_id',
        'sender_id',
        'sender_type',
        'body',
        'read'
    ];

    protected $casts = [
        'read' => 'bool',
    ];

    public function chat()
    {
        return $this->belongsTo(BookingChat::class, 'booking_chat_id');
    }

    public function userSender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function hotelSender()
    {
        return $this->belongsTo(Hotel::class, 'sender_id');
    }
}