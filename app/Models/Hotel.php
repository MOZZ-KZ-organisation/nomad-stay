<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $fillable = [
        'title','slug','description','address','city','country',
        'latitude','longitude','stars','is_active','min_price','type'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function rooms() { return $this->hasMany(Room::class); }
    public function amenities() { return $this->belongsToMany(Amenity::class); }
    public function images() { return $this->hasMany(HotelImage::class); }
    public function reviews() { return $this->hasMany(Review::class); }
}
