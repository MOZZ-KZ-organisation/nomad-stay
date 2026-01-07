@extends('voyager::master')

@section('css')
<style>
.chat-container {
    max-width: 900px;
    margin: 0 auto;
}

.chat-box {
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: #f9f9f9;
}

.message {
    display: flex;
    flex-direction: column;
    margin-bottom: 12px;
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 15px;
    word-wrap: break-word;
}

.from-user {
    align-self: flex-start;
    background: #e0f7fa;
}

.from-support {
    align-self: flex-end;
    background: #c8e6c9;
}

.message strong {
    font-weight: 600;
    margin-bottom: 5px;
}

.message small {
    font-size: 0.8rem;
    color: #666;
    margin-top: 5px;
    align-self: flex-end;
}

.chat-form {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.chat-form textarea {
    flex-grow: 1;
    resize: none;
}

</style>
@stop

@section('content')
<div class="page-content container-fluid chat-container">
    <h1 class="page-title">
        <i class="voyager-chat"></i> Чат с пользователем: {{ $chat->user->name }}
    </h1>

    <div class="chat-box" id="chatBox">
        @foreach($chat->messages as $message)
            <div class="message {{ $message->sender_id === auth()->id() ? 'from-support' : 'from-user' }}">
                <strong>{{ $message->sender_id === auth()->id() ? 'Поддержка' : $chat->user->name }}</strong>
                <span>{{ $message->body }}</span>
                <small>{{ $message->created_at->format('H:i d.m.Y') }}</small>
            </div>
        @endforeach
    </div>

    <form action="{{ route('voyager.support-messages.store', $chat->id) }}" method="POST" class="chat-form">
        @csrf
        <textarea name="body" class="form-control" rows="3" placeholder="Напишите сообщение..." required></textarea>
        <button type="submit" class="btn btn-success">Отправить</button>
    </form>
</div>
@stop

@section('javascript')
<script>
    const chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>
@stop