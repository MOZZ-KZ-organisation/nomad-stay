<?php

namespace App\Http\Resources;

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
            'city' => $this->city,
            'country' => $this->country,
            'stars' => $this->stars,
            'rating' => round($this->reviews()->avg('rating') ?? 0, 2),
            'reviews_count' => $this->reviews()->count(),
            'price_for_period' => $this->min_price, // mobile will compute price_for_nights * nights
            'discount_percent' => $this->when(isset($this->discount_percent), $this->discount_percent),
            'is_favorite' => false, // подставлять если авторизован
            'main_image' => $this->images->first()?->path
                ? Voyager::image($this->images->first()->path)
                : Voyager::image($this->images()->first()->path ?? ''),
        ];
    }
}
