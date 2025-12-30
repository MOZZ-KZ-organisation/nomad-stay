<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotelShowRequest;
use App\Http\Resources\HotelRecentResource;
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
        $isFavorite = $user
            ? $user->favorites()->where('hotel_id', $hotel->id)->exists()
            : false;
        $hotel->is_favorite = $isFavorite;
        $hotel->is_favorite = $isFavorite;
        $hotel->rooms = $hotel->rooms
            ->filter(fn($room) => $room->available_stock > 0 && $room->capacity >= $guests)
            ->values();
        return new HotelResource($hotel);
    }

    public function recent()
    {
        $hotels = Hotel::query()
            ->where('is_active', true)
            ->with(['images' => function ($q) {
                $q->orderByDesc('is_main');
            }])
            ->inRandomOrder()
            ->limit(6)->get();
        return HotelRecentResource::collection($hotels);
    }
}
