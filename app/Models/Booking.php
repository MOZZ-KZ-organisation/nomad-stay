<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id', 'hotel_id', 'room_id',
        'start_date', 'end_date', 'guests', 'price_for_period', 'tax', 'total_price', 'status', 'first_name','last_name',
        'email',
        'country',
        'phone',
        'is_business_trip',
        'special_requests',
        'arrival_time',
    ];

    protected $casts = [
        'is_business_trip' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'arrival_time' => 'datetime:H:i',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'hotel_id', 'hotel_id')
            ->where('user_id', $this->user_id);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
