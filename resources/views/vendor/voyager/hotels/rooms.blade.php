@php
    $roomTitles = $data->rooms->pluck('title')->toArray();
    $titlesString = implode(', ', $roomTitles);
    $shortText = \Illuminate\Support\Str::limit($titlesString, 25);
@endphp
@if($data->rooms->count())
    <a href="{{ route('voyager.rooms.index', ['hotel_id' => $data->id]) }}"
       style="text-decoration:none; color:#18506b;">
        <p style="margin:0;">{{ $shortText }}</p>
    </a>
@else
    <span class="text-muted">Нет номеров</span>
@endif