<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelDiscount extends Model
{
    protected $fillable = [
        'hotel_id',
        'discount_percent',
        'price_override',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}