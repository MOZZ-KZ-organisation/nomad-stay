@extends('voyager::master')
@section('content')
<style>
.calendar-table {
    border-collapse: collapse;
    width: 100%;
    margin: 1rem;
}
h1{
    font-size: 26px;
    margin: 1.2rem;
}
.calendar-table th,
.calendar-table td {
    border: 1px solid #eee;
    padding: 6px;
    min-width: 80px;
    text-align: center;
}

.calendar-table td {
    padding: 3px;
    background: #fff;
    vertical-align: middle;
}
.booking-bg {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 55px;
    padding: 10px;
    border-radius: 8px;
}
.booking-content {
    background: #fff;
    color: #333;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 13px;
    line-height: 1.3;
    text-align: left;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
.price-cell {
    height: 100%;
    min-height: 55px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size: 13px;
    color: #444;
    background: #F7F8FA;
    border-radius:8px;
}
</style>

<h1>Брони и заявки</h1>
<table class="calendar-table">
    <thead>
    <tr>
        <th>Номер</th>
        @foreach($dates as $date)
            <th>{{ $date->translatedFormat('d M') }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($rooms as $room)
        <tr>
            <td>
                {{ $room->number ?? $room->title }} <br>
                <small>{{ $room->hotel->title }}</small>
            </td>
            @foreach($dates as $date)
                @php
                    $booking = $bookings->first(function($b) use ($room, $date) {
                        return $b->room_id == $room->id &&
                               $date->between(
                                    \Carbon\Carbon::parse($b->start_date),
                                    \Carbon\Carbon::parse($b->end_date)->subDay()
                               );
                    });
                @endphp
                <td>
                    @if($booking)
                        <div class="booking-bg" style="background: {{ $booking->color }}">
                            <div class="booking-content">
                                {{ $booking->full_name }} <br>
                                {{ number_format($booking->total_price, 0, '.', ' ') }} ₸
                            </div>
                        </div>
                    @else
                        <div class="price-cell">
                            {{ number_format($room->price, 0, '.', ' ') }} ₸
                        </div>
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
@endsection