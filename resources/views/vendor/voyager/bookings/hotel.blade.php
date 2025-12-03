@php
    use App\Models\Hotel;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    $currentRoute = Route::currentRouteName();
    $isBrowse = Str::contains($currentRoute, ['index', 'browse']);
    $isRead = Str::contains($currentRoute, ['show', 'read']);
    $isEditOrAdd = Str::contains($currentRoute, ['edit', 'create']);
    $item = $data ?? $dataTypeContent ?? null;
    $currentHotelId = old('hotel_id') ?? ($item->hotel_id ?? null);
    $currentHotel = $currentHotelId ? Hotel::find($currentHotelId) : null;
@endphp
@if($isBrowse || $isRead)
    <span>{{ $currentHotel?->title ?? '—' }}</span>
@elseif($isEditOrAdd)
    <input type="text" class="form-control" value="{{ $currentHotel?->title ?? '—' }}" readonly>
    <input type="hidden" name="hotel_id" value="{{ $currentHotel?->id ?? '' }}">
@endif