<?php

namespace App\Http\Controllers;

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
            'sender_type' => 'support',
            'body' => $request->body,
            'read' => false,
        ]);
        return redirect()
            ->back()
            ->with('success', 'Сообщение успешно отправлено.');
    }
}
