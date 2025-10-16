@php
    // Определяем текущее действие (browse или read)
    $isBrowse = request()->routeIs('voyager.hotels.index');
    $isRead = request()->routeIs('voyager.hotels.show') || request()->routeIs('voyager.hotels.read');

    $roomTitles = $data->rooms->pluck('title')->toArray();
    $titlesString = implode(', ', $roomTitles);
    $shortText = \Illuminate\Support\Str::limit($titlesString, 30);
@endphp
@if($data->rooms->count())
    @if($isBrowse)
        {{-- В режиме списка (browse) показываем короткий текст --}}
        <a href="{{ route('voyager.rooms.index', ['hotel_id' => $data->id]) }}"
           style="text-decoration:none; color:#2aa0ad;">
            <p style="margin:0;">{{ $shortText }}</p>
        </a>
    @elseif($isRead)
        {{-- В режиме деталей (read/show) показываем список номеров --}}
        <div style="display:flex; flex-direction:column; gap:4px;">
            @foreach($data->rooms as $room)
                <a href="{{ route('voyager.rooms.index', ['hotel_id' => $data->id]) }}"
                   style="text-decoration:none; color:#1f2937;">
                    <p style="margin:0;">{{ \Illuminate\Support\Str::limit($room->title, 30) }}</p>
                </a>
            @endforeach
        </div>
    @endif
@else
    <span class="text-muted">Нет номеров</span>
@endif