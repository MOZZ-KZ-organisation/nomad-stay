<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingChat extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'hotel_id',
        'last_message_at'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function hotel() { return $this->belongsTo(Hotel::class); }

    public function messages()
    {
        return $this->hasMany(BookingMessage::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(BookingMessage::class)->latestOfMany();
    }
}