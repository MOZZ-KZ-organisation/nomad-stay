<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingMiniResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hotel' => new HotelMiniResource($this->whenLoaded('hotel')),
            'start_date' => Carbon::parse($this->start_date)->format('d.m.Y'),
            'end_date' => Carbon::parse($this->end_date)->format('d.m.Y'),
            'total_price' => $this->total_price,
            'status' => $this->status,
        ];
    }
}
