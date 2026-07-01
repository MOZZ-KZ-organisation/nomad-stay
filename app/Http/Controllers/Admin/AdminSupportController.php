<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Events\SupportMessageSent;
use Illuminate\Http\Request;

class AdminSupportController extends Controller
{
    /**
     * GET /admin-api/support-chats
     * Список всех чатов поддержки.
     */
    public function index(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $chats = SupportChat::with(['user:id,name,email', 'lastMessage'])
            ->withCount(['messages as unread_count' => fn($q) =>
                $q->where('read', false)->where('sender_id', '!=', auth()->id())
            ])
            ->orderByDesc('last_message_at')
            ->paginate($request->get('per_page', 20));
        return response()->json([
            'data' => $chats->map(fn($c) => [
                'id'           => $c->id,
                'user'         => ['id' => $c->user?->id, 'name' => $c->user?->name, 'email' => $c->user?->email],
                'last_message' => $c->lastMessage ? [
                    'body'       => $c->lastMessage->body,
                    'created_at' => $c->lastMessage->created_at->format('d.m.Y H:i'),
                ] : null,
                'unread_count'    => $c->unread_count,
                'last_message_at' => $c->last_message_at?->format('d.m.Y H:i'),
            ]),
            'meta' => [
                'total'        => $chats->total(),
                'current_page' => $chats->currentPage(),
                'last_page'    => $chats->lastPage(),
            ],
        ]);
    }

    /**
     * GET /admin-api/support-chats/{id}
     * Сообщения чата поддержки + пометить прочитанными.
     */
    public function show(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $chat = SupportChat::with('user:id,name,email')->findOrFail($id);
        // Пометить как прочитанные
        $chat->messages()
            ->where('read', false)
            ->where('sender_id', '!=', auth()->id())
            ->update(['read' => true]);
        $messages = $chat->messages()
            ->with('sender:id,name')
            ->latest()
            ->paginate($request->get('per_page', 30));
        return response()->json([
            'chat' => [
                'id'   => $chat->id,
                'user' => ['id' => $chat->user?->id, 'name' => $chat->user?->name],
            ],
            'data' => $messages->map(fn($m) => [
                'id'          => $m->id,
                'body'        => $m->body,
                'sender_id'   => $m->sender_id,
                'sender_name' => $m->sender?->name,
                'is_admin'    => $m->sender_id === auth()->id(),
                'read'        => $m->read,
                'created_at'  => $m->created_at->format('d.m.Y H:i'),
            ])->reverse()->values(),
            'meta' => [
                'total'        => $messages->total(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * POST /admin-api/support-chats/{id}/messages
     * Отправить сообщение в чат поддержки.
     */
    public function reply(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $chat = SupportChat::findOrFail($id);
        $request->validate(['body' => 'required|string|max:2000']);
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'body'      => $request->body,
            'read'      => false,
        ]);
        $chat->update(['last_message_at' => now()]);
        // broadcast(new SupportMessageSent($message))->toOthers();
        return response()->json([
            'message' => 'Сообщение отправлено',
            'data' => [
                'id'         => $message->id,
                'body'       => $message->body,
                'sender_id'  => $message->sender_id,
                'is_admin'   => true,
                'created_at' => $message->created_at->format('d.m.Y H:i'),
            ],
        ], 201);
    }

    /**
     * GET /admin-api/booking-chats
     * Список чатов по бронированиям.
     */
    public function bookingChats(Request $request)
    {
        $user      = $request->user();
        $isManager = $user->isHotelManager();
        $query = \App\Models\BookingChat::with(['user:id,name,email', 'hotel:id,title', 'lastMessage'])
            ->withCount(['messages as unread_count' => fn($q) =>
                $q->where('read', false)->where('sender_id', '!=', auth()->id())
            ])
            ->orderByDesc('last_message_at');
        if ($isManager) {
            $query->where('hotel_id', $user->managedHotel?->id);
        }
        $chats = $query->paginate($request->get('per_page', 20));
        return response()->json([
            'data' => $chats->map(fn($c) => [
                'id'    => $c->id,
                'user'  => ['id' => $c->user?->id, 'name' => $c->user?->name],
                'hotel' => ['id' => $c->hotel?->id, 'title' => $c->hotel?->title],
                'last_message' => $c->lastMessage ? [
                    'body'       => $c->lastMessage->body,
                    'created_at' => $c->lastMessage->created_at->format('d.m.Y H:i'),
                ] : null,
                'unread_count'    => $c->unread_count,
                'last_message_at' => $c->last_message_at?->format('d.m.Y H:i'),
            ]),
            'meta' => [
                'total'        => $chats->total(),
                'current_page' => $chats->currentPage(),
                'last_page'    => $chats->lastPage(),
            ],
        ]);
    }

    /**
     * GET /admin-api/booking-chats/{id}
     * Сообщения конкретного booking-чата.
     */
    public function showBookingChat(Request $request, $id)
    {
        $user = $request->user();
        $chat = \App\Models\BookingChat::with(['user:id,name', 'hotel:id,title'])->findOrFail($id);
        if ($user->isHotelManager() && $user->managedHotel?->id !== $chat->hotel_id) {
            abort(403);
        }
        $chat->messages()
            ->where('read', false)
            ->where('sender_id', '!=', auth()->id())
            ->update(['read' => true]);

        $messages = $chat->messages()
            ->with('userSender:id,name')
            ->oldest()
            ->paginate($request->get('per_page', 50));
        return response()->json([
            'chat' => [
                'id'    => $chat->id,
                'user'  => ['id' => $chat->user?->id, 'name' => $chat->user?->name],
                'hotel' => ['id' => $chat->hotel?->id, 'title' => $chat->hotel?->title],
            ],
            'data' => $messages->map(fn($m) => [
                'id'         => $m->id,
                'body'       => $m->body,
                'sender_id'  => $m->sender_id,
                'sender_name'=> $m->userSender?->name,
                'is_admin'   => $m->sender_id === auth()->id(),
                'read'       => $m->read,
                'created_at' => $m->created_at->format('d.m.Y H:i'),
            ]),
            'meta' => [
                'total'        => $messages->total(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * POST /admin-api/booking-chats/{id}/messages
     * Ответить в booking-чат.
     */
    public function replyBookingChat(Request $request, $id)
    {
        $user = $request->user();
        $chat = \App\Models\BookingChat::findOrFail($id);
        if ($user->isHotelManager() && $user->managedHotel?->id !== $chat->hotel_id) {
            abort(403);
        }
        $request->validate(['body' => 'required|string|max:2000']);
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'body'      => $request->body,
            'read'      => false,
        ]);
        $chat->update(['last_message_at' => now()]);
        broadcast(new \App\Events\BookingMessageSent($message))->toOthers();
        return response()->json([
            'message' => 'Сообщение отправлено',
            'data' => [
                'id'         => $message->id,
                'body'       => $message->body,
                'sender_id'  => $message->sender_id,
                'is_admin'   => true,
                'created_at' => $message->created_at->format('d.m.Y H:i'),
            ],
        ], 201);
    }
}