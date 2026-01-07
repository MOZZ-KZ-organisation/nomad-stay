<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingChatResource;
use App\Models\Booking;
use App\Models\BookingChat;
use Illuminate\Http\Request;

class BookingChatController extends Controller
{
    public function index()
    {
        $chats = BookingChat::with(['hotel.images', 'lastMessage'])
            ->where('user_id', auth()->id())
            ->orderByDesc('last_message_at')
            ->get();
        return BookingChatResource::collection($chats);
    }

    public function create(Booking $booking)
    {
        abort_if($booking->user_id !== auth()->id(), 403);
        $chat = BookingChat::firstOrCreate([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'hotel_id' => $booking->hotel_id,
        ]);
        return new BookingChatResource($chat);
    }
}
