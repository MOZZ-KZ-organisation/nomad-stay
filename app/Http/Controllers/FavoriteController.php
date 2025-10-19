<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFavoriteRequest;
use App\Http\Resources\HotelFavListResource;
use App\Models\Hotel;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(StoreFavoriteRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $hotelId = $data['hotel_id'];
        $isFavorite = $user->favorites()->where('hotel_id', $hotelId)->exists();
        if ($isFavorite) {
            // Удаляем, если уже в избранном
            $user->favorites()->detach($hotelId);
            return response()->json([
                'message' => 'Удалено из избранного',
                'is_favorite' => false
            ]);
        }
        // Добавляем, если ещё нет
        $user->favorites()->attach($hotelId, [
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'guests' => $data['guests'] ?? null
        ]);
        return response()->json([
            'message' => 'Добавлено в избранное',
            'is_favorite' => true
        ]);
    }

    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()
            ->with([
                'images' => fn($q) => $q->where('is_main', true),
                'city.country'
            ])
            ->withPivot(['start_date', 'end_date', 'guests'])
            ->select('hotels.id', 'hotels.title', 'hotels.slug', 'hotels.city_id', 'hotels.stars', 'hotels.min_price')
            ->get();
        return HotelFavListResource::collection($favorites);
    }
}
