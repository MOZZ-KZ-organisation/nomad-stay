@php
    use App\Models\Hotel;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    $isBrowse = Str::contains(Route::currentRouteName(), ['index', 'browse']);
    $isRead = Str::contains(Route::currentRouteName(), ['show', 'read']);
    $isEditOrAdd = Str::contains(Route::currentRouteName(), ['edit', 'create']);
    $item = $data ?? $dataTypeContent ?? null;
    // Определяем ID отеля
    $currentHotelId =
        old('hotel_id') ??
        request('hotel_id') ??
        ($item->hotel_id ?? null);
    $currentHotel = $currentHotelId ? Hotel::find($currentHotelId) : null;
@endphp
@if($isBrowse || $isRead)
    {{-- Отображаем только название --}}
    <span>{{ $currentHotel?->title ?? '—' }}</span>
@elseif($isEditOrAdd)
    {{-- В режиме добавления/редактирования --}}
    @php($hotels = Hotel::all(['id','title']))
    <select class="form-control select2" name="hotel_id" id="hotel_id">
        <option value="">Выберите отель</option>
        @foreach($hotels as $hotel)
            <option value="{{ $hotel->id }}" {{ $hotel->id == $currentHotelId ? 'selected' : '' }}>
                {{ $hotel->title }}
            </option>
        @endforeach
    </select>
@endif