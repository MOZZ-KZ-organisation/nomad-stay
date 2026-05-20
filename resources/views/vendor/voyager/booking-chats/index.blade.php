@extends('voyager::master')
@section('css')
    @if(auth()->user()->isAdmin())
        <style>
            /* Скрываем пункт чатов для админа */
            .nav-item a[href="{{ url('/admin/booking-chats') }}"] {
                display: none !important;
            }
        </style>
    @endif
@endsection
@section('content')
<div style="max-width:900px; margin:24px auto;">

    <h2 style="font-size:22px; font-weight:700; margin-bottom:20px;">
        Чаты с гостями
    </h2>

    @forelse($chats as $chat)
        @php $last = $chat->lastMessage; @endphp
        <a href="{{ route('admin.booking-chats.show', $chat->id) }}"
           style="display:flex; align-items:center; gap:14px; padding:16px 20px;
                  background:#fff; border-radius:14px; margin-bottom:10px;
                  box-shadow:0 2px 8px rgba(0,0,0,0.06); text-decoration:none; color:#111;
                  transition: box-shadow 0.2s;"
           onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.1)'"
           onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'">

            {{-- Аватар --}}
            <div style="width:46px; height:46px; border-radius:50%; background:#111827;
                        color:#fff; display:flex; align-items:center; justify-content:center;
                        font-weight:700; font-size:16px; flex-shrink:0;">
                {{ mb_substr($chat->user->name, 0, 1) }}
            </div>

            {{-- Инфо --}}
            <div style="flex:1; min-width:0;">
                <div style="font-weight:600; font-size:14px;">
                    {{ $chat->user->name }}
                </div>
                <div style="font-size:12px; color:#6b7280; margin-top:2px;">
                    {{ $chat->hotel->title }}
                </div>
                <div style="font-size:13px; color:#9ca3af; margin-top:4px;
                            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    {{ $last?->body ?? 'Нет сообщений' }}
                </div>
            </div>

            {{-- Время + непрочитанные --}}
            <div style="text-align:right; flex-shrink:0;">
                <div style="font-size:12px; color:#9ca3af;">
                    {{ $last?->created_at->format('d.m H:i') }}
                </div>
                @php
                    $unread = $chat->messages()
                        ->where('read', false)
                        ->where('sender_id', '!=', auth()->id())
                        ->count();
                @endphp
                @if($unread)
                    <div style="margin-top:6px; display:inline-flex; align-items:center;
                                justify-content:center; width:20px; height:20px;
                                background:#ef4444; color:#fff; border-radius:50%;
                                font-size:11px; font-weight:700;">
                        {{ $unread }}
                    </div>
                @endif
            </div>

        </a>
    @empty
        <div style="text-align:center; color:#9ca3af; padding:40px;">
            Нет активных чатов
        </div>
    @endforelse

</div>
@endsection