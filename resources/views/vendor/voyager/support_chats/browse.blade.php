@extends('voyager::master')

@section('page_title', 'Чаты поддержки')
@section('page_header')
<div class="container-fluid">
    <h1 class="page-title">
        <i class="voyager-chat"></i> Чаты поддержки
    </h1>
</div>
@stop

@section('content')
<div class="page-content browse container-fluid">
    @include('voyager::alerts')
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="dataTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Пользователь</th>
                                    <th>Последнее сообщение</th>
                                    <th>Время последнего сообщения</th>
                                    <th class="text-right">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dataTypeContent as $chat)
                                    <tr>
                                        <td>{{ $chat->user->name }}</td>
                                        <td>{{ Str::limit($chat->lastMessage->body ?? '', 50) }}</td>
                                        <td>{{ $chat->last_message_at ? $chat->last_message_at->diffForHumans() : '-' }}</td>
                                        <td class="no-sort no-click bread-actions text-right">
                                            <a href="{{ route('voyager.support-chats.show', $chat->id) }}" class="btn btn-sm btn-primary">
                                                <i class="voyager-chat"></i> Открыть чат
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{-- Пагинация --}}
                        @if($isServerSide)
                        <div class="pull-left">
                            <div role="status" class="show-res" aria-live="polite">
                                {{ trans_choice(
                                    'voyager::generic.showing_entries', $dataTypeContent->total(), [
                                        'from' => $dataTypeContent->firstItem(),
                                        'to' => $dataTypeContent->lastItem(),
                                        'all' => $dataTypeContent->total()
                                    ]) }}
                            </div>
                        </div>
                        <div class="pull-right">
                            {{ $dataTypeContent->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
@if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
<link rel="stylesheet" href="{{ voyager_asset('lib/css/responsive.dataTables.min.css') }}">
@endif
@stop

@section('javascript')
@if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
<script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
@endif
<script>
$(document).ready(function () {
    @if (!$dataType->server_side)
    $('#dataTable').DataTable({
        "order": [],
        "language": {!! json_encode(__('voyager::datatable')) !!},
        "columnDefs": [
            { "targets": 'no-sort', "orderable": false }
        ]
    });
    @endif
});
</script>
@stop