<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RateRule extends Model
{
    use SoftDeletes;

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