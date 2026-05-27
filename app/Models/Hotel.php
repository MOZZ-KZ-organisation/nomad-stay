<?php

namespace App\Models;

use App\Models\Traits\HandlesImages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Hotel extends Model
{
    use HandlesImages;
    protected $fillable = [
        'manager_id', 'title','slug','description','address','city_id',
        'latitude','longitude','stars','is_active','min_price', 'cancellation_fee',
        'type', 'email'
    ];
    protected $appends = ['location'];
    protected $casts = ['is_active' => 'boolean'];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function rooms() { return $this->hasMany(Room::class); }
    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'amenity_hotel', 'hotel_id', 'amenity_id');
    }
    public function images() { return $this->hasMany(HotelImage::class); }
    public function nearby()
    {
        return $this->hasOne(HotelNearby::class);
    }
    public function reviews() { return $this->hasMany(Review::class); }

    protected static function booted()
    {
        static::saving(function ($hotel) {
            if (empty($hotel->slug)) {
                $hotel->slug = \Str::slug($hotel->title);
            }
        });
        static::created(function ($hotel) {
            $hotel->discount()->create([
                'discount_percent' => 0,
                'price_override'   => $hotel->min_price ?? 0, // Подхватит логику, если min_price есть
            ]);
        });
        static::saved(function ($hotel) {
            Cache::flush();
        });
        static::deleted(function ($hotel) {
            Cache::flush();
            $hotel->rooms()->each(function ($room) {
                $room->images()->each(function ($image) {
                    if ($image->path) {
                        $filePath = public_path('storage/' . $image->path);
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                    }
                    $image->delete();
                });
                $room->delete();
            });
            $hotel->images()->each(function ($image) {
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

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function discount()
    {
        return $this->hasOne(HotelDiscount::class);
    }
}
