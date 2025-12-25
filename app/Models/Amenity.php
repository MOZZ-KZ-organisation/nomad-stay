<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $fillable = ['name'];

    public function hotels()
    {
        return $this->belongsToMany(Hotel::class, 'amenity_hotel', 'amenity_id', 'hotel_id');
    }
}
