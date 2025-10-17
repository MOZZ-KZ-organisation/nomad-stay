<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TCG\Voyager\Facades\Voyager;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price,
            'beds' => $this->beds,
            'capacity' => $this->capacity,
            'bathrooms' => $this->bathrooms,
            'stock' => $this->stock,
            'images' => $this->images->map(fn($i) => Voyager::image($i->path))
        ];
    }
}
