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
        $start = $this->pivot->start_date ?? null;
        $end = $this->pivot->end_date ?? null;
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
            'price_for_period' => $nights ? $this->min_price * max(1, $nights) : null,
            // данные из pivot
            'start_date' => $start,
            'end_date' => $end,
            'guests' => (int) ($this->pivot->guests ?? 0),
            'discount_percent' => $this->when(isset($this->discount_percent), $this->discount_percent),
            'is_favorite' => true,
            'main_image' => $this->images->first()?->path
                ? Voyager::image($this->images->first()->path)
                : Voyager::image($this->images()->first()->path ?? ''),
        ];
    }
}