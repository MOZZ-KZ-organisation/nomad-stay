<?php

namespace App\Http\Controllers;

use App\Events\SupportMessageSent;
use App\Http\Requests\StoreBookingMessageRequest;
use App\Http\Resources\SupportMessageResource;
use App\Models\SupportChat;
use Illuminate\Http\Request;

class SupportMessageController extends Controller
{
    public function store(StoreBookingMessageRequest $request)
    {
        $chat = SupportChat::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'body' => $request->body,
        ]);
        $chat->update(['last_message_at' => now()]);
        broadcast(new SupportMessageSent($message))->toOthers();
        return new SupportMessageResource($message);
    }
}
