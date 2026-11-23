<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Тариф на период по датам: цена за ночь для набора номеров отеля.
 * Границы date_from / date_to включительно, по датам ночей.
 */
class PeriodRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'title',
        'date_from',
        'date_to',
        'price',
        'is_active',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'period_rate_room');
    }

    public function scopeActive($query)
    {
        return $query->where('period_rates.is_active', true);
    }

    /**
     * Тарифы, период которых пересекается с [$from, $to] (даты включительно).
     */
    public function scopeOverlapping($query, string $from, string $to)
    {
        return $query->where('period_rates.date_from', '<=', $to)
            ->where('period_rates.date_to', '>=', $from);
    }
}
