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

        // cache key depends on all filter params + page
        $cacheKey = 'search:' . md5(json_encode(array_merge($data, ['page' => $page])));

        $hotels = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($data) {
            $q = Hotel::query()->where('is_active', true);

            if (!empty($data['city'])) { $q->where('city', $data['city']); }
            if (!empty($data['country'])) { $q->where('country', $data['country']); }
            if (!empty($data['type'])) { $q->where('type', $data['type']); }
            if (!empty($data['price_min'])) { $q->where('min_price', '>=', $data['price_min']); }
            if (!empty($data['price_max'])) { $q->where('min_price', '<=', $data['price_max']); }
            if (!empty($data['stars'])) { $q->whereIn('stars', $data['stars']); }
            if (!empty($data['q'])) {
                $q->where(function($sub) use ($data) {
                    $sub->where('title','like','%'.$data['q'].'%')
                        ->orWhere('description','like','%'.$data['q'].'%');
                });
            }
            $q->select(['id','title','slug','city','country','stars','min_price'])
              ->with(['images' => function($iq){ $iq->select('id','hotel_id','path','is_main')->where('is_main', true); }]);
            return $q->orderBy('min_price','asc')->simplePaginate(20);
        });
        return HotelListResource::collection($hotels);
    }
}
