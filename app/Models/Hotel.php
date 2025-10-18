<?php

namespace App\Models;

use App\Models\Traits\HandlesImages;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HandlesImages;
    protected $fillable = [
        'title','slug','description','address','city_id',
        'latitude','longitude','stars','is_active','min_price','type'
    ];
    protected $appends = ['location'];
    protected $casts = ['is_active' => 'boolean'];

    public function rooms() { return $this->hasMany(Room::class); }
    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'amenity_hotel', 'hotel_id', 'amenity_id');
    }
    public function images() { return $this->hasMany(HotelImage::class); }
    public function reviews() { return $this->hasMany(Review::class); }

    protected static function booted()
    {
        static::saving(function ($hotel) {
            if (empty($hotel->slug)) {
                $hotel->slug = \Str::slug($hotel->title);
            }
        });
        static::deleting(function ($hotel) {
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
}
