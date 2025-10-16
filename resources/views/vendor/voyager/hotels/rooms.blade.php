@php
    $count = $data->rooms->count();
    $url = route('voyager.rooms.index', ['hotel_id' => $data->id]);
@endphp

@if($count > 0)
    <a href="{{ $url }}" class="text-primary">
        {{ $count }} {{ Str::plural('номер', $count) }}
    </a>
@else
    <span class="text-muted">Нет номеров</span>
@endif