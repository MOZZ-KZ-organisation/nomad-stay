<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hotel' => new HotelMiniResource($this->whenLoaded('hotel')),
            'room' => new RoomMiniResource($this->whenLoaded('room')),
            'start_date' => Carbon::parse($this->start_date)->format('d.m.Y'),
            'end_date' => Carbon::parse($this->end_date)->format('d.m.Y'),
            'guests' => $this->guests,
            'price_for_period' => $this->price_for_period,
            'tax' => $this->tax,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'guest' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'country' => $this->country,
                'phone' => $this->phone,
                'is_business_trip' => (bool)$this->is_business_trip,
                'special_requests' => $this->special_requests,
                'arrival_time' => Carbon::parse($this->arrival_time)->format('H:i'),
            ],
        ];
    }
}
