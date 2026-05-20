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

    public function create(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
        ]);

        $chat = BookingChat::firstOrCreate([
            'user_id'  => auth()->id(),
            'hotel_id' => $request->hotel_id,
        ]);
        return new BookingChatResource($chat);
    }
}