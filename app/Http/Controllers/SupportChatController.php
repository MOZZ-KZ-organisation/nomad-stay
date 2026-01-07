<?php

namespace App\Http\Controllers;

use App\Http\Resources\SupportChatResource;
use App\Http\Resources\SupportMessageResource;
use App\Models\SupportChat;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    public function index()
    {
        $chat = SupportChat::with('lastMessage')
            ->where('user_id', auth()->id())
            ->first();
        if (!$chat) {
            return response()->json([
                'data' => [],
            ]);
        }
        return SupportChatResource::collection(collect([$chat]));
    }

    public function show()
    {
        $chat = SupportChat::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
        $chat->messages()
            ->where('read', false)
            ->where('sender_id', '!=', auth()->id())
            ->update(['read' => true]);
        $messages = $chat->messages()
            ->with('sender')
            ->latest()
            ->paginate(20);
        return response()->json([
            'messages' => $this->groupMessagesByDate($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'has_more' => $messages->hasMorePages(),
            ],
        ]);
    }

    protected function groupMessagesByDate($paginator)
    {
        return $paginator->getCollection()
            ->groupBy(fn ($message) => $message->created_at->toDateString())
            ->map(function ($messages, $date) {
                $carbonDate = Carbon::parse($date);
                return [
                    'date' => $this->humanDate($carbonDate),
                    'messages' => SupportMessageResource::collection($messages),
                ];
            })
            ->values();
    }

    protected function humanDate(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'Сегодня';
        }
        if ($date->isYesterday()) {
            return 'Вчера';
        }
        return $date->format('d.m.Y');
    }
}
