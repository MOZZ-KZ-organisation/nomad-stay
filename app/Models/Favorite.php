<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = ['user_id', 'hotel_id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
