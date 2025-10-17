@php
    use App\Models\Hotel;
    use Illuminate\Support\Facades\Route;
    $isBrowse = Str::contains(Route::currentRouteName(), ['index', 'browse']);
    $isRead = Str::contains(Route::currentRouteName(), ['show', 'read']);
    $isEditOrAdd = Str::contains(Route::currentRouteName(), ['edit', 'create']);
    $item = $data ?? $dataTypeContent ?? null;
    $hotels = Hotel::all(['id', 'title']);
    $currentHotelId = old($row->field, request('hotel_id') ?? $item->{$row->field} ?? null);
    $currentHotel = $hotels->firstWhere('id', $currentHotelId);
@endphp
@if($isBrowse || $isRead)
    {{-- Просто отображаем название отеля --}}
    <span>{{ $currentHotel->title ?? '—' }}</span>
@elseif($isEditOrAdd)
    {{-- В режиме создания/редактирования — выпадающий список --}}
    <select class="form-control select2" name="{{ $row->field }}" id="{{ $row->field }}">
        <option value="">Выберите отель</option>
        @foreach($hotels as $hotel)
            <option value="{{ $hotel->id }}" {{ $hotel->id == $currentHotelId ? 'selected' : '' }}>
                {{ $hotel->title }}
            </option>
        @endforeach
    </select>
@endif