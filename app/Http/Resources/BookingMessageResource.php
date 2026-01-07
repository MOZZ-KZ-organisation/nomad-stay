<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingMessageResource extends JsonResource
{
    public function toArray($request)
    {
        $isMine = $this->sender_type === 'user'
            && $this->sender_id === auth()->id();
        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_mine' => $isMine,
            'read' => $this->read,
            'time' => $this->created_at->format('H:i'),
            'date' => $this->created_at->toDateString(),
        ];
    }
}
