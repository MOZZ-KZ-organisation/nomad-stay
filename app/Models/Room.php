<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Facades\Voyager;

class Room extends Model
{
    protected $fillable = ['hotel_id','title','slug','description','capacity','beds','bathrooms','price','stock'];

    public function hotel(){ return $this->belongsTo(Hotel::class); }
    public function images(){ return $this->hasMany(RoomImage::class); }
        public function getImageUrlsAttribute()
    {
        return $this->images->map(fn($img) => Voyager::image($img->path))->toArray();
    }
}
