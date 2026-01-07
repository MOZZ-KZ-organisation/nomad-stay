<?php

namespace App\Http\Controllers;

use App\Models\SupportChat;
use Illuminate\Http\Request;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class VoyagerSupportChatsController extends VoyagerBaseController
{
    public function show(Request $request, $id)
    {
        $response = parent::show($request, $id);
        $chat = SupportChat::with('messages')->findOrFail($id);
        $chat->messages()
            ->where('sender_id', '!=', auth()->id())
            ->where('read', false)
            ->update(['read' => true]);
        return view('vendor.voyager.support-chats.read', compact('chat'));
    }
}
