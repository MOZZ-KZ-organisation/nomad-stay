<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Resources\HotelListResource;
use App\Http\Resources\HotelWithDiscountResource;
use App\Models\Hotel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index(SearchRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $start  = Carbon::parse($data['start_date']);
        $end    = Carbon::parse($data['end_date']);
        $nights = $start->diffInDays($end);
        $q = Hotel::query()
            ->where('hotels.is_active', true)
            ->select([
                'hotels.id',
                'hotels.title',
                'hotels.slug',
                'hotels.city_id',
                'hotels.stars',
                'hotels.min_price',
                'hotels.latitude',
                'hotels.longitude',
            ])
            ->with([
                'images' => fn ($q) => $q->where('is_main', true),
                'city.country',
                'discount:id,hotel_id,discount_percent,price_override',
            ]);
        if (!empty($data['city_id'])) {
            $q->where('hotels.city_id', $data['city_id']);
        }
        if (!empty($data['type'])) {
            $q->where('hotels.type', $data['type']);
        }
        if (!empty($data['price_min'])) {
            $q->where('hotels.min_price', '>=', $data['price_min']);
        }
        if (!empty($data['price_max'])) {
            $q->where('hotels.min_price', '<=', $data['price_max']);
        }
        if (!empty($data['stars'])) {
            $q->whereIn('hotels.stars', $data['stars']);
        }
        if (!empty($data['amenities'])) {
            $amenityIds = $data['amenities'];
            $q->whereIn('hotels.id', function ($sub) use ($amenityIds) {
                $sub->select('ah.hotel_id')
                    ->from('amenity_hotel as ah')
                    ->whereIn('ah.amenity_id', $amenityIds)
                    ->groupBy('ah.hotel_id')
                    ->havingRaw(
                        'COUNT(DISTINCT ah.amenity_id) = ?',
                        [count($amenityIds)]
                    );
            });
        }
        $q->whereExists(function ($sub) use ($start, $end, $data) {
            $sub->select(DB::raw(1))
                ->from('rooms')
                ->whereColumn('rooms.hotel_id', 'hotels.id')
                ->where(function ($roomQuery) use ($start, $end, $data) {
                    if (!empty($data['guests'])) {
                        $roomQuery->where('rooms.capacity', '>=', $data['guests']);
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
        });
        $hotels = $q
            ->orderBy('hotels.min_price', 'asc')
            ->simplePaginate(10);
        $favoritesIds = $user
            ? $user->favorites()->pluck('hotel_id')->toArray()
            : [];
        $hotels->getCollection()->transform(function (Hotel $hotel) use ($favoritesIds, $nights) {
            $hotel->is_favorite = in_array($hotel->id, $favoritesIds);
            $hotel->nights = $nights;
            return $hotel;
        });
        $specialOffers = Hotel::query()
            ->where('hotels.is_active', true)->whereHas('discount')
            ->select([
                'hotels.id',
                'hotels.title',
                'hotels.slug',
                'hotels.city_id',
                'hotels.stars',
            ])
            ->with([
                'images' => fn ($q) => $q->where('is_main', true),
                'city',
                'discount:id,hotel_id,discount_percent,price_override',
            ])->limit(6)->get();
        return response()->json([
            'data' => HotelListResource::collection($hotels),
            'special_offers' => HotelWithDiscountResource::collection($specialOffers),
            'meta' => [
                'current_page' => $hotels->currentPage(),
                'per_page' => $hotels->perPage(),
                'has_more' => $hotels->hasMorePages(),
            ],
        ]);
    }
}
