<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingMiniResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hotel' => new HotelMiniResource($this->whenLoaded('hotel')),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'price' => $this->price,
            'status' => $this->status,
        ];
    }
}
