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
        return [
            'id' => $this->id,
            'title' => $this->title,
            'location' => "{$this->city->name}, {$this->city->country->name}",
            'stars' => $this->stars,
            'rating' => round($this->reviews()->avg('rating') ?? 0, 2),
            'reviews_count' => $this->reviews()->count(),
            'price_per_night' => $this->min_price,
            'price_for_period' => $this->min_price * $this->nights,
            'discount_percent' => $this->when(isset($this->discount_percent), $this->discount_percent),
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'main_image' => $this->images->first()?->path
                ? Voyager::image($this->images->first()->path)
                : Voyager::image($this->images()->first()->path ?? ''),
        ];
    }
}
