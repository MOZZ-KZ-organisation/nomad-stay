<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelRecentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = $this->images->first();
        return [
            'title'   => $this->title,
            'address' => $this->address,
            'image'   => $image
                ? url(asset('storage/' . $image->path))
                : null,
        ];
    }
}
