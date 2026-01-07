@extends('voyager::master')

@section('css')
<style>
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

.chat-header {
    padding: 12px;
    background: #f0f0f0;
    text-align: center;
    font-weight: bold;
}

.chat-box {
    flex-grow: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background-color: #b2d8e9;
}

.message {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 20px;
    word-wrap: break-word;
    font-size: 0.95rem;
}

.from-user {
    align-self: flex-start;
    background-color: #ffffff;
    border-top-left-radius: 0;
}

.from-support {
    align-self: flex-end;
    background-color: #dcf8c6;
    border-top-right-radius: 0;
}

.message small {
    font-size: 0.7rem;
    color: #666;
    display: block;
    text-align: right;
    margin-top: 4px;
}

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
</style>
@stop

@section('content')
<div class="page-content container-fluid chat-container">
    <div class="chat-header">
        Чат с пользователем: {{ $chat->user->name }}
    </div>

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
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
    const chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;

    Pusher.logToConsole = false;

    const pusher = new Pusher("{{ config('broadcasting.connections.pusher.key') }}", {
        cluster: "{{ config('broadcasting.connections.pusher.options.cluster') }}",
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }
    });

    const chatId = {{ $chat->id }};
    const authUserId = {{ auth()->id() }};
    const channel = pusher.subscribe('private-support-chat.' + chatId);

    channel.bind('support.message.sent', function (data) {
        appendMessage(data);
    });

    function appendMessage(message) {
        const div = document.createElement('div');
        div.classList.add('message');

        if (message.is_mine) {
            div.classList.add('from-support');
        } else {
            div.classList.add('from-user');
        }

        div.innerHTML = `
            ${escapeHtml(message.body)}
            <small>${message.time} ${message.date}</small>
        `;

        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }
</script>
@stop