@php
    use Illuminate\Support\Str;
    $route = request()->route()?->getName();
    $isBrowse = Str::contains($route, 'voyager.hotels.index');
    $isRead = Str::contains($route, ['voyager.hotels.show', 'voyager.hotels.read']);
    $isEdit = Str::contains($route, 'voyager.hotels.edit');
    $data = $data ?? $dataTypeContent ?? null;
    $roomTitles = $data?->rooms?->pluck('title')->toArray() ?? [];
    $titlesString = implode(', ', $roomTitles);
    $shortText = \Illuminate\Support\Str::limit($titlesString, 30);
@endphp
@if($data && $data->rooms->count())
    {{-- Если есть номера --}}
    @if($isBrowse)
        <a href="{{ route('voyager.rooms.index', ['hotel_id' => $data->id]) }}"
           style="text-decoration:none; color:#208590;">
            <p style="margin:0;">{{ $shortText }}</p>
        </a>
    @elseif($isRead || $isEdit)
        <div style="display:flex; flex-direction:column; gap:4px;">
            @foreach($data->rooms as $room)
                <a href="{{ route('voyager.rooms.show', $room->id) }}" style="text-decoration:none; color:#208590;">
                    <p style="margin:0;">{{ \Illuminate\Support\Str::limit($room->title, 50) }}</p>
                </a>
            @endforeach
        </div>
    @endif
@else
    {{-- Если номеров нет --}}
    <span class="text-muted">Нет номеров</span>
@endif
{{-- Кнопка "Добавить номер" — отображается в browse и read --}}
@if(($isRead || $isBrowse || $isEdit) && $data)
    <div style="margin-top:8px;">
        <a href="{{ route('voyager.rooms.create', ['hotel_id' => $data->id]) }}"
           class="btn btn-sm btn-success">
            Добавить номер
        </a>
    </div>
@endif