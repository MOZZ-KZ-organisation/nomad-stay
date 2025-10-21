<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TCG\Voyager\Facades\Voyager;

class HotelMiniResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'city' => $this->city->location,
            'address' => $this->address,
            'stars' => $this->stars,
            'main_image' => $this->images->first()?->path
                ? Voyager::image($this->images->first()->path)
                : null,
        ];
    }
}
