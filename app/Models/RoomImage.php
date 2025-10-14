<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Facades\Voyager;

class RoomImage extends Model
{
    protected $fillable = ['room_id', 'path', 'is_main'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function getUrlAttribute()
    {
        if (is_array($this->path)) {
            return array_map(fn($p) => Voyager::image($p), $this->path);
        }
        return [Voyager::image($this->path)];
    }
}
