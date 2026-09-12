<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingService extends Model
{
    protected $fillable = [
        'booking_id',
        'service_id',
        'quantity',
        'price',
        'amount',
        'comment',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    protected static function booted(): void
    {
        static::saving(function (BookingService $bs) {
            // amount всегда пересчитывается на бэкенде, фронт его не задаёт
            $bs->amount = $bs->quantity * $bs->price;
        });
    }
}