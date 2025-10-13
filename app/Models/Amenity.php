<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $fillable = ['name', 'slug'];

    public function hotels()
    {
        return $this->belongsToMany(Hotel::class);
    }

    protected static function booted()
    {
        static::saving(function ($amenity) {
            if (empty($amenity->slug)) {
                $amenity->slug = \Str::slug($amenity->name);
            }
        });
    }
}
