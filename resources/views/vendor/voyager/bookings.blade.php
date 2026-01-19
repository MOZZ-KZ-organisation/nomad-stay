@extends('voyager::master')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
.top-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 16px;
}
.calendar-wrapper {
    background: #fff;
    border-radius: 12px;
    margin: 16px;
    overflow: hidden;
}
.calendar-header,
.calendar-row {
    display: flex;
    position: relative;
}
.room-col {
    width: 160px;
    padding: 10px;
    border-right: 2px solid #e5e7eb;
    background: #fafafa;
    flex-shrink: 0;
}
.day-col {
    flex: 1;
    text-align: center;
    padding: 6px 0;
    font-size: 12px;
    border-right: 1px solid #e5e7eb;
}
.day-week {
    font-size: 11px;
    color: #6b7280;
}
.calendar-row {
    height: 64px;
    border-bottom: 1px solid #eee;
}
.row-body {
    position: relative;
    flex: 1;
    height: 64px;
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
    transition: box-shadow 0.2s ease;
}
.booking-bar:hover {
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
}
.filters-wrapper {
    position: relative;
}
.filter-panel {
    display: none;
    position: absolute;
    top: 45px;
    width: 280px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    padding: 15px;
    z-index: 999;
}
.room-hotel {
    font-size: 11px;
    color: #6b7280;
}
</style>
<div class="top-controls">
    <div style="display:flex;align-items:center;gap:8px;">
        <a class="btn btn-sm btn-default"
           href="{{ route('admin.bookings.calendar', array_merge(request()->all(), [
                'start' => $startDate->copy()->subDay()->toDateString()
           ])) }}">
            ←
        </a>
        <a class="btn btn-sm btn-default"
           href="{{ route('admin.bookings.calendar', array_merge(request()->all(), [
                'start' => $startDate->copy()->addDay()->toDateString()
           ])) }}">
            →
        </a>
    </div>
    <h1 style="font-size:26px;margin-left:12px;">
        Календарь бронирований
        <span style="font-size:14px;color:#6b7280;">
            {{ $dates->first()->format('d.m') }} – {{ $dates->last()->format('d.m') }}
        </span>
    </h1>
    <div class="filters-wrapper">
        <button id="filterBtn" class="btn btn-default">⚙️ Фильтр</button>
        <div id="filterPanel" class="filter-panel">
            <form id="filtersForm">
                <label>
                    <input type="checkbox" name="only_booked" value="1"
                        {{ request('only_booked') ? 'checked' : '' }}>
                    Только занятые
                </label>
                <div style="margin-top:10px;">
                    <label>Тип номера</label>
                    <select name="room_type" class="form-control">
                        <option value="">Все</option>
                        @foreach($roomTypes as $type)
                            <option value="{{ $type }}"
                                {{ request('room_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary btn-block" style="margin-top:12px;">
                    Применить
                </button>
            </form>
        </div>
    </div>
</div>
<div class="calendar-wrapper">
    <div class="calendar-header">
        <div class="room-col"><b>Номер</b></div>
        @foreach($dates as $date)
            <div class="day-col">
                <b>{{ $date->format('d') }}</b>
                <div class="day-week">
                    {{ $date->translatedFormat('dd') }}
                </div>
            </div>
        @endforeach
    </div>
    @foreach($rooms as $room)
        @php
            $roomBookings = $bookings->where('room_id', $room->id);
        @endphp
        <div class="calendar-row">
            <div class="room-col">
                <b>{{ $room->number ?? $room->title }}</b>
                <div class="room-hotel">{{ $room->hotel->title }}</div>
            </div>
            <div class="row-body">
                @foreach($dates as $i => $date)
                    <div class="day-bg"
                         style="left:{{ ($i / 18) * 100 }}%;
                                width:{{ 100 / 18 }}%;">
                    </div>
                @endforeach
                @foreach($roomBookings as $booking)
                    @php
                        $startIndex = max(
                            0,
                            $dates->search(fn($d) => $d->gte($booking->start_date))
                        );
                        $endIndex = $dates->search(
                            fn($d) => $d->gt($booking->end_date)
                        ) ?? $dates->count();
                        $left  = ($startIndex / 18) * 100;
                        $width = max(5, (($endIndex - $startIndex) / 18) * 100);
                    @endphp
                    <div class="booking-bar"
                         style="
                            left: {{ $left }}%;
                            width: {{ $width }}%;
                            background: {{ $booking->color }};
                         ">
                        {{ $booking->full_name }}
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterBtn   = document.getElementById('filterBtn');
    const filterPanel = document.getElementById('filterPanel');
    const filtersForm = document.getElementById('filtersForm');
    filterBtn.onclick = e => {
        e.stopPropagation();
        filterPanel.style.display =
            filterPanel.style.display === 'block' ? 'none' : 'block';
    };
    filtersForm.onclick = e => e.stopPropagation();
    filtersForm.onsubmit = e => {
        e.preventDefault();
        const params = new URLSearchParams(new FormData(filtersForm)).toString();
        location.search = params;
    };
    document.onclick = () => {
        filterPanel.style.display = 'none';
    };
});
</script>
@endsection