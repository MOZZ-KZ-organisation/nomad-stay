<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelWithDiscountResource extends JsonResource
{
    public function toArray($request)
    {
        $discount = $this->discount;
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'stars' => $this->stars,
            'city' => $this->city->name ?? null,
            'discount_percent' => $discount->discount_percent,
            'price_override' => $discount->price_override,
            'image' => $this->images->first()?->path,
        ];
    }
}
