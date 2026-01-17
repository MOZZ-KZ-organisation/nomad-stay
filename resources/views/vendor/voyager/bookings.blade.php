@extends('voyager::master')
@section('content')
<style>
.calendar-wrapper {
    background: #fff;
    border-radius: 12px;
    overflow-x: auto;
    margin: 16px;
}

.calendar-header,
.calendar-row {
    display: flex;
    position: relative;
}

.room-col {
    width: 150px;
    padding: 10px;
    border-right: 2px solid #e5e7eb;
    background: #fafafa;
    flex-shrink: 0;
}

.day-col {
    width: 60px;
    border-right: 1px solid #e5e7eb;
    text-align: center;
    padding: 6px 0;
    font-size: 12px;
}

.calendar-row {
    height: 64px;
    border-bottom: 1px solid #eee;
}

.row-body {
    position: relative;
    flex: 1;
    min-height: 64px;
}

.day-bg {
    position: absolute;
    top: 0;
    bottom: 0;
    background: #eff6ff;
    border-right: 1px solid #e5e7eb;
}

.booking-bar {
    position: absolute;
    top: 6px;
    bottom: 6px;
    border-radius: 8px;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 500;
    color: #111;
    display: flex;
    align-items: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    z-index: 10;
    transition: box-shadow .2s;
}

.booking-bar:hover {
    box-shadow: 0 6px 18px rgba(0,0,0,.15);
}
</style>

<h1 style="margin:16px;font-size:26px;">Календарь бронирований</h1>

<div class="calendar-wrapper">
    {{-- HEADER --}}
    <div class="calendar-header">
        <div class="room-col"><b>Номер</b></div>

        @foreach($dates as $date)
            <div class="day-col">
                <div><b>{{ $date->format('d') }}</b></div>
                <div style="font-size:11px;color:#666">
                    {{ $date->translatedFormat('dd') }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- ROOMS --}}
    @foreach($rooms as $room)
        @php
            $roomBookings = $bookings->where('room_id', $room->id);
        @endphp

        <div class="calendar-row">
            <div class="room-col">
                <div><b>{{ $room->number ?? $room->title }}</b></div>
                <div style="font-size:11px;color:#666">
                    {{ $room->hotel->title }}
                </div>
            </div>

            <div class="row-body" style="min-width: {{ $dates->count() * 60 }}px">

                {{-- BACKGROUND DAYS --}}
                @foreach($dates as $i => $date)
                    <div class="day-bg"
                         style="left: {{ $i * 60 }}px; width:60px;">
                    </div>
                @endforeach

                {{-- BOOKINGS --}}
                @foreach($roomBookings as $booking)
                    @php
                        $startIndex = max(
                            0,
                            $dates->search(fn($d) => $d->gte($booking->start_date))
                        );

                        $endIndex = min(
                            $dates->count(),
                            $dates->search(fn($d) => $d->gte($booking->end_date))
                        );

                        $left = $startIndex * 60 + 30;
                        $width = max(60, ($endIndex - $startIndex) * 60);
                    @endphp

                    <div class="booking-bar"
                        style="
                            left: {{ $left }}px;
                            width: {{ $width }}px;
                            background: {{ $booking->color }};
                        "
                        data-id="{{ $booking->id }}"
                        data-name="{{ $booking->full_name }}"
                        data-start="{{ $booking->start_date->translatedFormat('d F') }}"
                        data-end="{{ $booking->end_date->translatedFormat('d F') }}"
                        data-total="{{ number_format($booking->total_price, 0, '.', ' ') }}"
                    >
                        {{ $booking->full_name }}
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

{{-- POPOVER --}}
<div id="bookingPopover"
     style="
        position:absolute;
        display:none;
        background:#fff;
        padding:14px;
        border-radius:12px;
        box-shadow:0 10px 30px rgba(0,0,0,.2);
        width:260px;
        z-index:9999;
     ">
    <b id="popName"></b>
    <div style="margin-top:6px;font-size:13px;">
        <div id="popDates"></div>
        <div id="popTotal"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const pop = document.getElementById('bookingPopover');

    document.querySelectorAll('.booking-bar').forEach(bar => {
        bar.addEventListener('click', e => {
            e.stopPropagation();
            const r = bar.getBoundingClientRect();

            document.getElementById('popName').innerText = bar.dataset.name;
            document.getElementById('popDates').innerText =
                bar.dataset.start + ' – ' + bar.dataset.end;
            document.getElementById('popTotal').innerText =
                bar.dataset.total + ' ₸';

            pop.style.left = (window.scrollX + r.left + r.width/2 - 130) + 'px';
            pop.style.top  = (window.scrollY + r.top - pop.offsetHeight - 12) + 'px';
            pop.style.display = 'block';
        });
    });

    document.addEventListener('click', () => {
        pop.style.display = 'none';
    });
});
</script>
@endsection