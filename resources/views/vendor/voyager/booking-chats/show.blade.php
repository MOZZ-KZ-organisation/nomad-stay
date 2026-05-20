@extends('voyager::master')

@section('content')
<div style="max-width:700px; margin:24px auto;">

    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
        <a href="{{ route('admin.booking-chats.index') }}"
           style="width:38px; height:38px; border-radius:10px; border:1px solid #e5e7eb;
                  background:#fff; display:flex; align-items:center; justify-content:center;
                  text-decoration:none; color:#111;">
            ←
        </a>
        <div>
            <div style="font-weight:700; font-size:16px;">{{ $chat->user->name }}</div>
            <div style="font-size:12px; color:#6b7280;">{{ $chat->hotel->title }}</div>
        </div>
    </div>

    {{-- Сообщения --}}
    <div style="background:#fff; border-radius:16px; padding:20px;
                box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:16px;
                min-height:400px; display:flex; flex-direction:column; gap:12px;">

        @forelse($messages as $message)
            @php $isMe = $message->sender_id === auth()->id(); @endphp
            <div style="display:flex; justify-content:{{ $isMe ? 'flex-end' : 'flex-start' }};">
                <div style="max-width:70%; padding:10px 14px;
                            background:{{ $isMe ? '#111827' : '#f3f4f6' }};
                            color:{{ $isMe ? '#fff' : '#111' }};
                            border-radius:{{ $isMe ? '14px 14px 4px 14px' : '14px 14px 14px 4px' }};
                            font-size:14px; line-height:1.5;">
                    {{ $message->body }}
                    <div style="font-size:11px; color:{{ $isMe ? 'rgba(255,255,255,0.5)' : '#9ca3af' }};
                                margin-top:4px; text-align:right;">
                        {{ $message->created_at->format('H:i') }}
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align:center; color:#9ca3af; margin:auto;">
                Нет сообщений
            </div>
        @endforelse

    </div>

    {{-- Форма ответа --}}
    <form action="{{ route('admin.booking-chats.reply', $chat->id) }}" method="POST">
        @csrf
        <div style="display:flex; gap:10px;">
            <input
                type="text"
                name="body"
                placeholder="Написать ответ..."
                required
                style="flex:1; padding:12px 16px; border:1px solid #e5e7eb;
                       border-radius:12px; font-size:14px; outline:none;"
            >
            <button type="submit"
                    style="padding:12px 24px; background:#111827; color:#fff;
                           border:none; border-radius:12px; font-size:14px;
                           font-weight:600; cursor:pointer;">
                Отправить
            </button>
        </div>
    </form>

</div>
@endsection