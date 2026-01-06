<?php

namespace App\Http\Controllers;

use App\Events\BookingMessageSent;
use App\Http\Requests\StoreBookingMessageRequest;
use App\Http\Resources\BookingMessageResource;
use App\Models\BookingChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookingMessageController extends Controller
{
    public function index(BookingChat $chat)
    {
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
                'avatar' => url(Storage::url($chat->hotel->images->first()?->url)),
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


    public function store(StoreBookingMessageRequest $request, BookingChat $chat)
    {
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'body' => $request->body,
        ]);
        $chat->update(['last_message_at' => now()]);
        broadcast(new BookingMessageSent($message))->toOthers();
        return new BookingMessageResource($message);
    }
}
