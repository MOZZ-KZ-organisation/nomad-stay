<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'city' => $this->city,
            'country' => $this->country,
            'stars' => $this->stars,
            'rating' => round($this->reviews()->avg('rating') ?? 0, 2),
            'reviews_count' => $this->reviews()->count(),
            'images' => $this->images->map(fn($i) => $i->path),
            'amenities' => $this->amenities->pluck('name'),
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'nearby' => [
                'metro' => 'Abay, 2 km',
                'station' => 'Central, 7 km'
            ],
        ];
    }
}
