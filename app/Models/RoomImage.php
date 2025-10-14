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
}
