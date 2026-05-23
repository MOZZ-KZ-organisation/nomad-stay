<?php

namespace App\Http\Controllers;

use App\Events\BookingMessageSent;
use App\Http\Requests\StoreBookingMessageRequest;
use App\Http\Resources\BookingMessageResource;
use App\Models\Booking;
use App\Models\BookingChat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookingMessageController extends Controller
{
    public function index(Request $request)
    {
        $chat = BookingChat::where('hotel_id', $request->hotel_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$chat) {
            return response()->json([
                'messages' => [],
                'meta' => ['current_page' => 1, 'has_more' => false],
            ]);
        }
        $chat->messages()
            ->where('read', false)
            ->where('sender_id', '!=', auth()->id())
            ->update(['read' => true]);
        $messages = $chat->messages()->latest()->paginate(20);
        $firstImagePath = $chat->hotel->images->first()?->path;
        return response()->json([
            'hotel' => [
                'name'   => $chat->hotel->title,
                'avatar' => $firstImagePath ? url(Storage::url($firstImagePath)) : null,
            ],
            'messages' => $this->groupMessagesByDate($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'has_more'     => $messages->hasMorePages(),
            ],
        ]);
    }

    public function store(StoreBookingMessageRequest $request)
    {
        $chat = BookingChat::firstOrCreate(
            [
                'user_id'  => auth()->id(),
                'hotel_id' => $request->hotel_id,
            ],
            ['last_message_at' => now()]
        );
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'body'      => $request->body,
        ]);
        $chat->update(['last_message_at' => now()]);
        broadcast(new BookingMessageSent($message))->toOthers();
        return new BookingMessageResource($message);
    }

    protected function groupMessagesByDate($paginator)
    {
        return $paginator->getCollection()
            ->groupBy(fn ($message) => $message->created_at->toDateString())
            ->map(function ($messages, $date) {
                $carbonDate = Carbon::parse($date);
                return [
                    'date'     => $this->humanDate($carbonDate),
                    'messages' => BookingMessageResource::collection($messages),
                ];
            })
            ->values();
    }

    protected function humanDate(Carbon $date): string
    {
        if ($date->isToday()) return 'Сегодня';
        if ($date->isYesterday()) return 'Вчера';
        return $date->format('d.m.Y');
    }
}