<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SupportChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastMessage = $this->whenLoaded('lastMessage');

        return [
            'id' => $this->id,
            'title' => 'Поддержка',
            'last_message' => $lastMessage ? [
                'text' => Str::limit($lastMessage->body, 100),
                'sender' => $lastMessage->sender_id === auth()->id()
                    ? 'me'
                    : 'support',
                'last_message_at' => $lastMessage->created_at->diffForHumans(),
            ] : null,
        ];
    }
}
