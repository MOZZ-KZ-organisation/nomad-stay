@extends('voyager::master')

@section('content')
<div class="page-content container-fluid">
    <h1>Чаты поддержки</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Пользователь</th>
                <th>Последнее сообщение</th>
                <th>Время последнего сообщения</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataTypeContent as $chat)
                <tr>
                    <td>{{ $chat->user->name }}</td>
                    <td>{{ Str::limit($chat->lastMessage->body ?? '', 50) }}</td>
                    <td>{{ $chat->last_message_at ? $chat->last_message_at->diffForHumans() : '' }}</td>
                    <td>
                        <a href="{{ route('voyager.support-chats.show', $chat->id) }}" class="btn btn-sm btn-primary">
                            Открыть чат
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection