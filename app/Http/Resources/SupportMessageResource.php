<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_mine' => $this->sender_id === auth()->id(),
            'read' => $this->read,
            'time' => $this->created_at->format('H:i'),
            'date' => $this->created_at->toDateString(),
        ];
    }
}
