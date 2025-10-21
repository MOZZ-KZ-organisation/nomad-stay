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
        ['start_date' => $start, 'end_date' => $end, 'guests' => $guests] = $request->validated();
        $cacheKey = 'hotel_detail:' . $hotel->id;
        $hotel = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($hotel) {
            return $hotel->load([
                'images',
                'amenities',
                'rooms' => function ($q) {
                    $q->select('id', 'hotel_id', 'title', 'price', 'beds', 'capacity', 'bathrooms');
                },
                'reviews' => function ($q) {
                    $q->latest()->limit(10);
                },
                'nearby',
                'city'
            ]);
        });
        $isFavorite = $user->favorites()->where('hotel_id', $hotel->id)->exists();
        $hotel->is_favorite = $isFavorite;
        $hotel->rooms->each(function ($room) use ($start, $end) {
            $room->available_stock = $room->calculateAvailableStock($start, $end);
        });
        $hotel->rooms = $hotel->rooms
            ->filter(fn($room) => $room->available_stock > 0 && $room->capacity >= $guests)
            ->values();
        return new HotelResource($hotel);
    }
}
