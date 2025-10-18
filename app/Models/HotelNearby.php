<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelNearby extends Model
{
    protected $fillable = ['hotel_id', 'metro', 'station', 'park', 'airport'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
