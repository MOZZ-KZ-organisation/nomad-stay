<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityAttraction extends Model
{
    protected $fillable = [
        'city_id',
        'title',
        'description',
        'image_path',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}