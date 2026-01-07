<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookingChatResource extends JsonResource
{
    public function toArray($request)
    {
        $lastMessage = $this->whenLoaded('lastMessage');
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'hotel' => [
                'title' => $this->hotel->title,
                'icon' => url(Storage::url($this->hotel->images->first()?->path)),
            ],
            'last_message' => $lastMessage ? [
                'text' => Str::limit($lastMessage->body, 100),
                'sender' => $lastMessage->sender_id === auth()->id()
                    ? 'me'
                    : 'hotel',
                'last_message_at' => $lastMessage->created_at->diffForHumans(),
            ] : null,
        ];
    }
}
