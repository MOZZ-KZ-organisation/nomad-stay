<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['hotel_id','title','slug','description','capacity','beds','bathrooms','price','stock'];

    public function hotel(){ return $this->belongsTo(Hotel::class); }
    public function images(){ return $this->hasMany(RoomImage::class); }
}
