<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelOfferResource extends JsonResource
{
    public function toArray($request)
    {
        $image = $this->images->first();
        return [
            'id' => $this->id,
            'title' => $this->title,
            'city_id' => $this->city_id,
            'stars' => $this->stars,
            'min_price' => $this->min_price,
            'discount' => $this->discount ? [
                'percent' => $this->discount->discount_percent,
                'price_override' => $this->discount->price_override,
            ] : null,
            'address' => $this->address,
            'image'   => $image
                ? url(asset('storage/' . $image->path))
                : null,
        ];
    }
}
