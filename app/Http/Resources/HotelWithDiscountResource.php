<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class HotelWithDiscountResource extends JsonResource
{
    public function toArray($request)
    {
        $discount = $this->discount;
        return [
            'id' => $this->id,
            'title' => $this->title,
            'location' => "{$this->city->name}, {$this->city->country->name}",
            'stars' => $this->stars,
            'discount_percent' => $discount->discount_percent,
            'price_override' => $discount->price_override,
            'is_favorite' => $this->is_favorite,
            'image' => url(Storage::url($this->images->first()?->path)),
        ];
    }
}
