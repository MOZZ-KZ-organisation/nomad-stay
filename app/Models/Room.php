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
}
