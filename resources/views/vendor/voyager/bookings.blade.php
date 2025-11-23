@extends('voyager::master')
@section('content')
<style>
.calendar-table {
    border-collapse: collapse;
    width: 100%;
}

.calendar-table th,
.calendar-table td {
    border: 1px solid #eee;
    padding: 6px;
    min-width: 80px;
    text-align: center;
}

.booking.confirmed {
    background: #2b9fff;
    color: white;
    border-radius: 5px;
    padding: 5px;
}

.booking.pending {
    background: orange;
    color: white;
    border-radius: 5px;
    padding: 5px;
}

.booking.cancelled {
    background: pink;
    color: #222;
    border-radius: 5px;
    padding: 5px;
}
</style>

<h1>Брони и заявки</h1>
<table class="calendar-table">
    <thead>
    <tr>
        <th>Номер</th>
        @foreach($dates as $date)
            <th>{{ $date->format('d M') }}</th>
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
                        <div class="booking {{ $booking->status }}">
                            {{ $booking->full_name }} <br>
                            {{ number_format($booking->total_price) }} ₸
                        </div>
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
@endsection