<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRoomSegment extends Model
{
    protected $fillable = [
        'booking_id',
        'room_id',
        'check_in_at',
        'check_out_at',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}