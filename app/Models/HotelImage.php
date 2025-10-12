<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelImage extends Model
{
    protected $fillable = ['hotel_id', 'path', 'is_main'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
