<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomPeriod extends Model
{
    protected $fillable = [
        'room_id',
        'hotel_id',
        'status',
        'start_date',
        'end_date',
        'comment',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    // Цвета для календаря — аналогично броням
    public function getColorAttribute(): string
    {
        return match ($this->status) {
            'cleaning'    => '#F4A261', // оранжевый
            'maintenance' => '#9B9B9B', // серый
            default       => '#FFFFFF', // белый (free)
        };
    }

    public function room(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function hotel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}