<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Facades\Voyager;

class Room extends Model
{
    protected $fillable = ['hotel_id','title','slug','description','capacity','beds','bathrooms','price','stock'];

    public function hotel(){ return $this->belongsTo(Hotel::class); }
    public function images(){ return $this->hasMany(RoomImage::class); }
    public function getImagessAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode(html_entity_decode($value), true);
        if (is_array($decoded)) {
            return array_map(function ($img) {
                return Voyager::image($img);
            }, $decoded);
        }
        return [Voyager::image($value)];
    }
}
