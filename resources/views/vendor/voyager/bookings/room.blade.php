@php
    use App\Models\Room;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    $currentRoute = Route::currentRouteName();
    $isBrowse = Str::contains($currentRoute, ['index', 'browse']);
    $isRead = Str::contains($currentRoute, ['show', 'read']);
    $isEditOrAdd = Str::contains($currentRoute, ['edit', 'create']);
    $item = $data ?? $dataTypeContent ?? null;
    $currentRoomId = old('room_id') ?? ($item->room_id ?? null);
    $currentRoom = $currentRoomId ? Room::find($currentRoomId) : null;
@endphp
@if($isBrowse || $isRead)
    <span>{{ $currentRoom?->title ?? '—' }}</span>
@elseif($isEditOrAdd)
    <input type="text" class="form-control" value="{{ $currentRoom?->title ?? '—' }}" readonly>
    <input type="hidden" name="room_id" value="{{ $currentRoom?->id ?? '' }}">
@endif