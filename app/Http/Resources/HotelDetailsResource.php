<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city->location,
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
            'stars' => $this->stars,
            'rating' => round($this->reviews()->avg('rating') ?? 0, 2),
            'reviews_count' => $this->reviews()->count(),
            'cancellation_fee' => $this->cancellation_fee,
            'images' => $this->images->map(
                fn($i) => Voyager::image($i->path)
            ),
            'amenities' => $this->amenities->map(fn($a) => [
                'code' => $a->code,
                'name' => $a->name,
            ]),
            'nearby' => collect([
                [
                    'key' => 'Метро',
                    'description' => $this->nearby?->metro,
                ],
                [
                    'key' => 'Станция',
                    'description' => $this->nearby?->station,
                ],
                [
                    'key' => 'Парк',
                    'description' => $this->nearby?->park,
                ],
                [
                    'key' => 'Аэропорт',
                    'description' => $this->nearby?->airport,
                ],
            ])->filter(fn($item) => !empty($item['description']))
              ->values(),
            'is_favorite' => $this->is_favorite,
        ];
    }
}