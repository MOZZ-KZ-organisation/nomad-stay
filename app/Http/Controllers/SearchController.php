<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Resources\HotelListResource;
use App\Models\Hotel;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function index(SearchRequest $request)
    {
        $data = $request->validated();
        $page = $request->get('page', 1);
        $cacheKey = 'search:' . md5(json_encode(array_merge($data, ['page' => $page])));
        $hotels = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($data) {
            $q = Hotel::query()->where('is_active', true);
            if (!empty($data['city_id'])) {
                $q->where('city_id', $data['city_id']);
            }
            if (!empty($data['type'])) $q->where('type', $data['type']);
            if (!empty($data['price_min'])) $q->where('min_price', '>=', $data['price_min']);
            if (!empty($data['price_max'])) $q->where('min_price', '<=', $data['price_max']);
            if (!empty($data['stars'])) $q->whereIn('stars', $data['stars']);
            // if (!empty($data['q'])) {
            //     $q->where(fn($sub) =>
            //         $sub->where('title', 'like', "%{$data['q']}%")
            //             ->orWhere('description', 'like', "%{$data['q']}%")
            //     );
            // }
            // гибкие даты
            $flexibility = (int)($data['flexibility'] ?? 0);
            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                $start = \Carbon\Carbon::parse($data['start_date'])->subDays($flexibility);
                $end = \Carbon\Carbon::parse($data['end_date'])->addDays($flexibility);
                $q->whereHas('rooms', function ($roomQuery) use ($start, $end, $data) {
                    if (!empty($data['guests'])) {
                        $roomQuery->where('capacity', '>=', $data['guests']);
                    }
                    $roomQuery->whereDoesntHave('bookings', function ($bq) use ($start, $end) {
                        $bq->where(function ($q2) use ($start, $end) {
                            $q2->whereBetween('start_date', [$start, $end])
                                ->orWhereBetween('end_date', [$start, $end])
                                ->orWhere(function ($q3) use ($start, $end) {
                                    $q3->where('start_date', '<=', $start)
                                    ->where('end_date', '>=', $end);
                                });
                        });
                    });
                });
            }
            return $q->with(['images' => fn($iq) => $iq->where('is_main', true)])
                    ->select(['id','title','slug','city_id','stars','min_price'])
                    ->orderBy('min_price','asc')
                    ->simplePaginate(20);
        });
        return HotelListResource::collection($hotels);
    }
}
