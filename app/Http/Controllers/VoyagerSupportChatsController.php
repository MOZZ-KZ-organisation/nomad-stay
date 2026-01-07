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
            ->where('sender_type', 'user')
            ->where('read', false)
            ->update(['read' => true]);
        return view('vendor.voyager.support-chats.show', compact('chat'));
    }
}
