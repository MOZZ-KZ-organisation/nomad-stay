<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service->id,
                'title' => $this->service->title,
                'unit' => $this->service->unit,
            ]),
            'quantity' => $this->quantity,
            'price' => $this->price,
            'amount' => $this->amount,
            'comment' => $this->comment,
        ];
    }
}