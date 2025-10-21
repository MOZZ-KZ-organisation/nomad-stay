<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotelShowRequest;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Support\Facades\Cache;

class HotelController extends Controller
{
    public function show(HotelShowRequest $request, Hotel $hotel)
    {
        $user = $request->user();
        ['guests' => $guests] = $request->validated();
        $cacheKey = 'hotel_detail:' . $hotel->id;
        $hotel = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($hotel) {
            return $hotel->load([
                'images',
                'amenities',
                'rooms.images',
                'reviews' => function ($q) {
                    $q->latest()->limit(10);
                },
                'nearby',
                'city'
            ]);
        });
        $isFavorite = $user->favorites()->where('hotel_id', $hotel->id)->exists();
        $hotel->is_favorite = $isFavorite;
        $hotel->rooms = $hotel->rooms
            ->filter(fn($room) => $room->available_stock > 0 && $room->capacity >= $guests)
            ->values();
        return new HotelResource($hotel);
    }
}
