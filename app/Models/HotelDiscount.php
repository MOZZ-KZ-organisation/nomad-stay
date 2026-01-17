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

    protected static function booted()
    {
        static::saving(function (HotelDiscount $discount) {
            if (!$discount->hotel_id || !$discount->discount_percent) {
                return;
            }
            $hotel = $discount->hotel()->select('id', 'min_price')->first();
            $discount->price_override = (int) round(
                $hotel->min_price * (1 - $discount->discount_percent / 100)
            );
        });
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}