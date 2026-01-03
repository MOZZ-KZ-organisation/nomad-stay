<?php

namespace App\Models;

use App\Models\Traits\HandlesImages;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Facades\Voyager;

class Room extends Model
{
    use HandlesImages;
    protected $fillable = ['hotel_id','title','slug','description','capacity','beds','bathrooms','price','stock'];

    public function hotel(){ return $this->belongsTo(Hotel::class); }
    public function images(){ return $this->hasMany(RoomImage::class); }
    public function scopeFilterByHotel($query)
    {
        if (request()->has('hotel_id')) {
            $query->where('hotel_id', request('hotel_id'));
        }
    }
    protected static function booted()
    {
        static::saving(function ($room) {
            if (empty($room->slug)) {
                $room->slug = \Str::slug($room->title);
            }
        });
        static::saved(function ($room) {
            $room->hotel?->update([
                'min_price' => $room->hotel->rooms()->min('price')
            ]);
        });
        static::deleted(function ($room) {
            $room->hotel?->update([
                'min_price' => $room->hotel->rooms()->min('price')
            ]);
        });
        static::deleting(function ($room) {
            $room->images()->each(function ($image) {
                if ($image->path) {
                    $filePath = public_path('storage/' . $image->path);
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
                $image->delete();
            });
        });
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getAvailableStockAttribute()
    {
        $start = request('start_date');
        $end = request('end_date');
        if (!$start || !$end) {
            return $this->stock;
        }
        $bookedCount = Booking::where('room_id', $this->id)
            // ->where('status', 'confirmed')
            ->where('end_date', '>', $start)
            ->where('start_date', '<', $end)
            ->count();
        return max(0, $this->stock - $bookedCount);
    }
}
