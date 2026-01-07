<?php

namespace App\Http\Controllers;

use App\Events\BookingMessageSent;
use App\Http\Requests\StoreBookingMessageRequest;
use App\Http\Resources\BookingMessageResource;
use App\Models\Booking;
use App\Models\BookingChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookingMessageController extends Controller
{
    public function index(Booking $booking)
    {
        $chat = BookingChat::where('booking_id', $booking->id)->first();
        if (!$chat) {
            return response()->json([
                'messages' => [],
                'meta' => [
                    'current_page' => 1,
                    'has_more' => false,
                ],
            ]);
        }
        $chat->messages()
            ->where('read', false)
            ->where('sender_id', '!=', auth()->id())
            ->update(['read' => true]);
        $messages = $chat->messages()
            ->with('sender')
            ->latest()
            ->paginate(20);
        return response()->json([
            'hotel' => [
                'name' => $chat->hotel->title,
                'avatar' => url(Storage::url($chat->hotel->images->first()?->path)),
            ],
            'messages' => $this->groupMessagesByDate(
                BookingMessageResource::collection($messages)
            ),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'has_more' => $messages->hasMorePages(),
            ],
        ]);
    }

    protected function groupMessagesByDate($messages)
    {
        return $messages->collection
            ->groupBy('date')
            ->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'messages' => $items->values(),
                ];
            })
            ->values();
    }


    public function store(StoreBookingMessageRequest $request, Booking $booking)
    {
        $chat = BookingChat::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'user_id' => auth()->id(),
                'hotel_id' => $booking->hotel_id,
            ]
        );
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'body' => $request->body,
        ]);
        $chat->update(['last_message_at' => now()]);
        broadcast(new BookingMessageSent($message))->toOthers();
        return new BookingMessageResource($message);
    }
}
