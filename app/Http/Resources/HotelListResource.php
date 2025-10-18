<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TCG\Voyager\Facades\Voyager;

class HotelListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');
        // Количество ночей, если даты переданы
        $nights = null;
        if ($start && $end) {
            $nights = abs(Carbon::parse($end)->diffInDays(Carbon::parse($start)));
        }
        return [
            'id' => $this->id,
            'title' => $this->title,
            'location' => "{$this->city->name}, {$this->city->country->name}",
            'stars' => $this->stars,
            'rating' => round($this->reviews()->avg('rating') ?? 0, 2),
            'reviews_count' => $this->reviews()->count(),
            'price_per_night' => $this->min_price,
            // итоговая цена за период
            'price_for_period' => $nights ? $this->min_price * max(1, $nights) : null,
            // гибкость дат (если передана)
            'flexibility' => (int) $request->input('flexibility', 0),
            'discount_percent' => $this->when(isset($this->discount_percent), $this->discount_percent),
            'is_favorite' => false, // подставлять если авторизован
            'main_image' => $this->images->first()?->path
                ? Voyager::image($this->images->first()->path)
                : Voyager::image($this->images()->first()->path ?? ''),
        ];
    }
}
