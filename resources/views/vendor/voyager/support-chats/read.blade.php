@extends('voyager::master')

@section('css')
<style>
/* Контейнер чата */
.chat-container {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    height: 80vh;
    border: 1px solid #ddd;
    border-radius: 10px;
    overflow: hidden;
    background-color: #f5f5f5;
}

/* Чат-бокс с сообщениями */
.chat-box {
    flex-grow: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background-color: #e5ddd5;
    scroll-behavior: smooth;
}

/* Стили для сообщений */
.message {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 20px;
    position: relative;
    display: inline-block;
    word-wrap: break-word;
    line-height: 1.4;
    font-size: 0.95rem;
}

/* Сообщения пользователя (слева) */
.from-user {
    align-self: flex-start;
    background-color: #ffffff;
    border-top-left-radius: 0;
}

/* Сообщения поддержки (справа) */
.from-support {
    align-self: flex-end;
    background-color: #dcf8c6;
    border-top-right-radius: 0;
}

/* Время в правом нижнем углу */
.message small {
    font-size: 0.7rem;
    color: #999;
    display: block;
    text-align: right;
    margin-top: 5px;
}

/* Форма ввода */
.chat-form {
    display: flex;
    padding: 10px;
    border-top: 1px solid #ddd;
    background-color: #f0f0f0;
}

.chat-form textarea {
    flex-grow: 1;
    border-radius: 20px;
    padding: 10px 15px;
    border: 1px solid #ccc;
    resize: none;
    outline: none;
}

.chat-form button {
    margin-left: 10px;
    border-radius: 20px;
    padding: 0 20px;
}

/* Скролл бар для чата */
.chat-box::-webkit-scrollbar {
    width: 6px;
}

.chat-box::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 3px;
}
</style>
@stop

@section('content')
<div class="page-content container-fluid chat-container">
    <h3 class="text-center" style="padding: 10px; background: #f0f0f0; margin:0;">Чат с пользователем: {{ $chat->user->name }}</h3>

    <div class="chat-box" id="chatBox">
        @foreach($chat->messages as $message)
            <div class="message {{ $message->sender_id === auth()->id() ? 'from-support' : 'from-user' }}">
                {{ $message->body }}
                <small>{{ $message->created_at->format('H:i d.m.Y') }}</small>
            </div>
        @endforeach
    </div>

    <form action="{{ route('voyager.support-messages.store', $chat->id) }}" method="POST" class="chat-form">
        @csrf
        <textarea name="body" rows="2" placeholder="Напишите сообщение..." required></textarea>
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