@extends('voyager::master')

@section('content')
<div class="page-content container-fluid">
    <h1>Чат с пользователем: {{ $chat->user->name }}</h1>

    <div class="chat-box" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
        @foreach($chat->messages as $message)
            <div class="message {{ $message->sender_type == 'support' ? 'from-support' : 'from-user' }}" style="margin-bottom: 10px;">
                <strong>{{ $message->sender_type == 'support' ? 'Поддержка' : $message->chat->user->name }}</strong>:
                <span>{{ $message->body }}</span>
                <small class="text-muted" style="display:block">{{ $message->created_at->format('H:i d.m.Y') }}</small>
            </div>
        @endforeach
    </div>

    <form action="{{ route('voyager.support-messages.store', $chat->id) }}" method="POST" style="margin-top: 15px;">
        @csrf
        <div class="form-group">
            <textarea name="body" class="form-control" rows="3" placeholder="Ваше сообщение..." required></textarea>
        </div>
        <button type="submit" class="btn btn-success">Отправить</button>
    </form>
</div>
@endsection