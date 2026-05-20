<?php

namespace App\Http\Controllers;

use App\Models\BookingChat;
use App\Models\BookingMessage;
use Illuminate\Http\Request;
use App\Events\BookingMessageSent;

class AdminBookingChatController extends Controller
{
    public function index()
    {
        $chats = BookingChat::with(['user', 'lastMessage', 'hotel'])
            ->orderByDesc('last_message_at')
            ->get();
        return view('vendor.voyager.booking-chats.index', compact('chats'));
    }

    public function show(BookingChat $chat)
    {
        if (auth()->user()->isHotelManager()) {
            abort_if($chat->hotel_id !== auth()->user()->managedHotel?->id, 403);
        }
        $chat->messages()
            ->where('read', false)
            ->where('sender_id', '!=', auth()->id())
            ->update(['read' => true]);
        $messages = $chat->messages()->with('sender')->oldest()->get();
        return view('vendor.voyager.booking-chats.show', compact('chat', 'messages'));
    }

    public function reply(Request $request, BookingChat $chat)
    {
        if (auth()->user()->isHotelManager()) {
            abort_if($chat->hotel_id !== auth()->user()->managedHotel?->id, 403);
        }
        $request->validate(['body' => 'required|string|max:1000']);
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'body'      => $request->body,
            'read'      => false,
        ]);
        $chat->update(['last_message_at' => now()]);
        broadcast(new BookingMessageSent($message))->toOthers();
        return back()->with('success', 'Сообщение отправлено');
    }
}