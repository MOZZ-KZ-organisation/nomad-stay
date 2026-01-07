<?php

namespace App\Http\Controllers;

use App\Events\SupportMessageSent;
use App\Models\SupportChat;
use Illuminate\Http\Request;

class VoyagerSupportMessageController extends Controller
{
    public function store(Request $request, SupportChat $chat)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'body' => $request->body,
            'read' => false,
        ]);
        $chat->update(['last_message_at' => now()]);
        broadcast(new SupportMessageSent($message))->toOthers();
        return redirect()
            ->back()
            ->with('success', 'Сообщение успешно отправлено.');
    }
}
