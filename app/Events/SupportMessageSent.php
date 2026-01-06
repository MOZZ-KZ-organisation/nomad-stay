<?php

namespace App\Events;

use App\Http\Resources\SupportMessageResource;
use App\Models\SupportMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public SupportMessage $message) {}

    public function broadcastOn()
    {
        return new PrivateChannel(
            'support-chat.' . $this->message->support_chat_id
        );
    }

    public function broadcastAs()
    {
        return 'support.message.sent';
    }

    public function broadcastWith()
    {
        return (new SupportMessageResource($this->message))->resolve();
    }
}