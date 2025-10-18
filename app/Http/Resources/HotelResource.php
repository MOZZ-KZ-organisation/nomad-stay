<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TCG\Voyager\Facades\Voyager;

class HotelResource extends JsonResource
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
            'stars' => $this->stars,
            'rating' => round($this->reviews()->avg('rating') ?? 0, 2),
            'reviews_count' => $this->reviews()->count(),
            'images' => $this->images->map(fn($i) => Voyager::image($i->path)),
            'amenities' => $this->amenities->pluck('name'),
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'nearby' => [
                'metro' => $this->nearby?->metro,
                'station' => $this->nearby?->station,
                'park' => $this->nearby?->park,
                'airport' => $this->nearby?->airport,
            ]
        ];
    }
}
