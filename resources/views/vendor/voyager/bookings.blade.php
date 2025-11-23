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

.calendar-table td {
    padding: 3px;   /* меньше, чтобы плитки были компактнее */
    background: #fff;
}

.booking {
    display: block;
    height: 100%;
    min-height: 55px;
    padding: 6px 6px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.3;
    text-align: left;

    /* ВАЖНО: оставляем лёгкую прозрачность как в дизайне */
    background: var(--booking-color);
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
                        <div class="booking" style="--booking-color: {{ $booking->color }}aa;">
                            <strong>{{ $booking->full_name }}</strong><br>
                            {{ $booking->total_price }} ₸
                        </div>
                    @else
                        <div class="price-cell">
                            {{ $room->price }} ₸
                        </div>
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
@endsection