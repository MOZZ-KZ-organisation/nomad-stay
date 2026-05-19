@php
    use App\Models\Hotel;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    $isBrowse = Str::contains(Route::currentRouteName(), ['index', 'browse']);
    $isRead = Str::contains(Route::currentRouteName(), ['show', 'read']);
    $isEditOrAdd = Str::contains(Route::currentRouteName(), ['edit', 'create']);
    $item = $data ?? $dataTypeContent ?? null;
    $user = auth()->user();
    $isManager = $user->isHotelManager();

    $currentHotelId = $isManager
        ? $user->managedHotel?->id
        : (old('hotel_id') ?? request('hotel_id') ?? ($item->hotel_id ?? null));

    $currentHotel = $currentHotelId ? Hotel::find($currentHotelId) : null;
@endphp
@if($isBrowse || $isRead)
    <span>{{ $currentHotel?->title ?? '—' }}</span>
@elseif($isEditOrAdd)
    @if($isManager)
        {{-- Менеджер видит только свой отель, без выбора --}}
        <input type="hidden" name="hotel_id" value="{{ $currentHotelId }}">
        <p class="form-control-static" style="padding-top:7px; font-weight:600; color:#374151;">
            {{ $currentHotel?->title ?? '—' }}
        </p>
    @else
        {{-- Админ выбирает из всех отелей --}}
        @php $hotels = Hotel::all(['id', 'title']) @endphp
        <select class="form-control select2" name="hotel_id" id="hotel_id">
            <option value="">Выберите отель</option>
            @foreach($hotels as $hotel)
                <option value="{{ $hotel->id }}" {{ $hotel->id == $currentHotelId ? 'selected' : '' }}>
                    {{ $hotel->title }}
                </option>
            @endforeach
        </select>
    @endif
@endif