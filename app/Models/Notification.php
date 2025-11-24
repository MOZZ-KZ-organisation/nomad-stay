<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'title',
        'source',
        'booking_id',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}