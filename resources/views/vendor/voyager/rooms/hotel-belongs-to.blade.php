@php
    use App\Models\Hotel;
    $hotels = Hotel::all();
    $currentHotelId = old($row->field, request('hotel_id') ?? $data->{$row->field} ?? null);
@endphp
<select class="form-control select2" name="{{ $row->field }}" id="{{ $row->field }}">
    <option value="">Выберите отель</option>
    @foreach($hotels as $hotel)
        <option value="{{ $hotel->id }}" {{ $hotel->id == $currentHotelId ? 'selected' : '' }}>
            {{ $hotel->title }}
        </option>
    @endforeach
</select>
