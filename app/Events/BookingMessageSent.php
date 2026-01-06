<?php

namespace App\Events;

use App\Http\Resources\BookingMessageResource;
use App\Models\BookingMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class BookingMessageSent implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public BookingMessage $message) {}

    public function broadcastOn()
    {
        return new PrivateChannel(
            'booking-chat.' . $this->message->booking_chat_id
        );
    }

    public function broadcastAs()
    {
        return 'booking.message.sent';
    }

    public function broadcastWith()
    {
        return (new BookingMessageResource($this->message))->resolve();
    }
}