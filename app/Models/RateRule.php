<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateRule extends Model
{
    protected $fillable = [
        'hotel_id',
        'type',
        'from_time',
        'to_time',
        'calc_type',
        'value',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}