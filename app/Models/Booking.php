<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'hotel_id',
        'room_id',
        'start_date',
        'end_date',
        'guests',
        'price_for_period',
        'tax',
        'total_price',
        'status',         
        'type',             
        'source',           
        'first_name',
        'last_name',
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

    public function getColorAttribute()
    {
        return match ($this->status) {
            'confirmed' => '#2D9CDB',  // синий (бронь)
            'pending'   => '#F2994A',  // оранжевый (заявка)
            'cancelled' => '#EB5757',  // красный
            'archived'  => '#BDBDBD',  // серый
            default     => '#9B51E0',  // фиолетовый
        };
    }

    public function scopeOverlapping($query, $start, $end)
    {
        return $query
            ->where('end_date', '>', $start)
            ->where('start_date', '<', $end);
    }

    public function scopeForCalendar($query, $start, $end)
    {
        return $query->whereBetween('start_date', [$start, $end])
            ->orWhereBetween('end_date', [$start, $end])
            ->orWhere(function ($q) use ($start, $end) {
                $q->where('start_date', '<', $start)
                  ->where('end_date', '>', $end);
            });
    }

    public function getPricePerNightAttribute()
    {
        $nights = $this->start_date->diffInDays($this->end_date);
        return $nights > 0
            ? $this->total_price / $nights
            : $this->total_price;
    }
}
