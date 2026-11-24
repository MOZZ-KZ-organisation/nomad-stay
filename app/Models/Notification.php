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
        'hotel_id',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    /**
     * Уведомления, которые видит пользователь админки: менеджер отеля — только
     * своего отеля, администратор — все (или одного отеля через $hotelId).
     */
    public function scopeVisibleTo($query, User $user, ?int $hotelId = null)
    {
        if ($user->isHotelManager()) {
            // Менеджер без отеля не видит ничего; чужой hotel_id из запроса игнорируется.
            return $query->where('notifications.hotel_id', $user->managedHotel?->id ?? 0);
        }

        return $hotelId ? $query->where('notifications.hotel_id', $hotelId) : $query;
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}