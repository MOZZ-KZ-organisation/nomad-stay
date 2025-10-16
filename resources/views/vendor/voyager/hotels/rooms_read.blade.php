@if($dataTypeContent->rooms->count())
    <div style="display:flex; flex-direction:column; gap:4px;">
        @foreach($dataTypeContent->rooms as $room)
            <a href="{{ route('voyager.rooms.index', ['hotel_id' => $dataTypeContent->id]) }}"
               style="text-decoration:none; color:#1f2937;"
               onmouseover="this.style.color='#2563eb';"
               onmouseout="this.style.color='#1f2937';">
                <p style="margin:0;">
                    {{ \Illuminate\Support\Str::limit($room->title, 30) }}
                </p>
            </a>
        @endforeach
    </div>
@else
    <span class="text-muted">Нет номеров</span>
@endif
