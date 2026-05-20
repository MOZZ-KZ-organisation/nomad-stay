<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotelShowRequest;
use App\Http\Resources\HotelDetailsResource;
use App\Http\Resources\HotelOfferResource;
use App\Http\Resources\HotelRecentResource;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\Request;
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

    public function details(Request $request, Hotel $hotel)
    {
        $user = $request->user();
        $cacheKey = 'hotel_details_only:' . $hotel->id;
        $hotel = Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($hotel) {
                return $hotel->load([
                    'images',
                    'amenities',
                    'reviews' => function ($q) {
                        $q->latest()->limit(10);
                    },
                    'nearby',
                    'city',
                ]);
            }
        );
        $isFavorite = $user
            ? $user->favorites()
                ->where('hotel_id', $hotel->id)
                ->exists()
            : false;
        $hotel->is_favorite = $isFavorite;
        return new HotelDetailsResource($hotel);
    }

    public function offers()
    {
        $hotels = Hotel::query()
            ->where('is_active', true)
            ->whereHas('discount', function ($query) {
                $query->where('discount_percent', '>', 0);
            })->with(['discount', 'city'])
            ->join('hotel_discounts', 'hotels.id', '=', 'hotel_discounts.hotel_id')
            ->orderByDesc('hotel_discounts.discount_percent')
            ->select('hotels.*')->paginate(10);
        return HotelOfferResource::collection($hotels);
    }
}
