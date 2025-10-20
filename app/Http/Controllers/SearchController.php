<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Resources\HotelListResource;
use App\Models\Hotel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function index(SearchRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $page = $request->get('page', 1);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $nights = $start->diffInDays($end);
        $cacheKey = 'search:' . md5(json_encode(array_merge($data, ['page' => $page])));
        $hotels = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($data, $start, $end) {
            $q = Hotel::query()
                ->where('is_active', true)
                ->select(['id', 'title', 'slug', 'city_id', 'stars', 'min_price'])
                ->with(['images' => fn($q) => $q->where('is_main', true), 'city.country', 'rooms']);
            if (!empty($data['city_id'])) $q->where('city_id', $data['city_id']);
            if (!empty($data['type'])) $q->where('type', $data['type']);
            if (!empty($data['price_min'])) $q->where('min_price', '>=', $data['price_min']);
            if (!empty($data['price_max'])) $q->where('min_price', '<=', $data['price_max']);
            if (!empty($data['stars'])) $q->whereIn('stars', $data['stars']);
            // Проверка на доступность хотя бы одной комнаты
            $q->whereHas('rooms', function ($roomQuery) use ($start, $end, $data) {
                if (!empty($data['guests'])) {
                    $roomQuery->where('capacity', '>=', $data['guests']);
                }
                $roomQuery->whereRaw("
                    (
                        SELECT COUNT(*) FROM bookings 
                        WHERE bookings.room_id = rooms.id
                        AND bookings.status = 'confirmed'
                        AND bookings.end_date > ?
                        AND bookings.start_date < ?
                    ) < rooms.stock
                ", [$start, $end]);
            });
            return $q->orderBy('min_price', 'asc')->simplePaginate(10);
        });
        $favoritesIds = $user ? $user->favorites()->pluck('hotel_id')->toArray() : [];
        $hotels->getCollection()->transform(function (Hotel $hotel) use ($favoritesIds, $nights) {
            $hotel->is_favorite = in_array($hotel->id, $favoritesIds);
            $hotel->nights = $nights;
            return $hotel;
        });
        return HotelListResource::collection($hotels);
    }
}
