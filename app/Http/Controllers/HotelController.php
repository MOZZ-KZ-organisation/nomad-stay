<?php

namespace App\Http\Controllers;

use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HotelController extends Controller
{
    public function show(Hotel $hotel)
    {
        $cacheKey = 'hotel_detail:' . $hotel->id;
        $hotel = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($hotel) {
            return $hotel->load([
                'images',
                'amenities',
                'rooms' => function ($q) {
                    $q->select('id', 'hotel_id', 'title', 'price', 'stock', 'beds', 'capacity', 'bathrooms');
                },
                'reviews' => function ($q) {
                    $q->latest()->limit(10);
                }
            ]);
        });
        return new HotelResource($hotel);
    }
}
