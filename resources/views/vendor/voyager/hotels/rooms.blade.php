@if($data->rooms->count())
    <div style="display:flex; flex-direction:column; gap:4px;">
        @foreach($data->rooms as $room)
            <a href="{{ route('voyager.rooms.edit', $room->id) }}" class="text-primary" style="text-decoration:none;">
                {{ \Illuminate\Support\Str::limit($room->title, 50) }}
            </a>
        @endforeach
    </div>
@else
    <span class="text-muted">Нет номеров</span>
@endif