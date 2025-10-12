<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hotel' => new HotelListResource($this->whenLoaded('hotel')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'guests' => $this->guests,
            'price' => $this->price,
            'status' => $this->status,
        ];
    }
}
