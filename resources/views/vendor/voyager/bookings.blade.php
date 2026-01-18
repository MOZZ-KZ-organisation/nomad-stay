@extends('voyager::master')
@section('content')
<style>
/* ====== КАЛЕНДАРЬ ====== */
.calendar-wrapper {
    background: #fff;
    border-radius: 12px;
    overflow-x: auto;
    margin: 16px;
}
.calendar-header,
.calendar-row {
    display: flex;
}
.room-col {
    width: 160px;
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
    background: #f8fafc;
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
    cursor: pointer;
    z-index: 10;
    white-space: nowrap;
    overflow: hidden;
}
.booking-bar:hover {
    box-shadow: 0 6px 18px rgba(0,0,0,.15);
}
</style>

{{-- ===== TOP BAR ===== --}}
<div style="display:flex;align-items:center;gap:12px;margin:16px;">
    <h1 style="font-size:26px;">Календарь бронирований</h1>

    {{-- 🔔 Notifications --}}
    <div style="position:relative;">
        <button id="notificationBell" class="btn btn-light">🔔
            <span id="notificationCount"
                style="display:none;position:absolute;top:-6px;right:-6px;
                background:red;color:#fff;border-radius:50%;font-size:11px;padding:2px 6px;">0</span>
        </button>
        <div id="notificationPanel"
             style="display:none;position:absolute;top:45px;width:320px;
             background:#fff;border-radius:12px;
             box-shadow:0 10px 30px rgba(0,0,0,.15);padding:10px;z-index:9999;">
            <b>Уведомления</b>
            <div id="notificationsList"></div>
        </div>
    </div>

    {{-- 🛈 Legend --}}
    <div style="position:relative;">
        <button id="legendBtn" class="btn btn-light">🛈</button>
        <div id="legendPanel"
             style="display:none;position:absolute;top:45px;width:220px;
             background:#fff;border-radius:12px;padding:12px;
             box-shadow:0 10px 30px rgba(0,0,0,.12);z-index:999;">
            <div><span style="background:#FACC15;width:14px;height:14px;display:inline-block"></span> booked</div>
            <div><span style="background:#22C55E;width:14px;height:14px;display:inline-block"></span> checked in</div>
            <div><span style="background:#9CA3AF;width:14px;height:14px;display:inline-block"></span> checked out</div>
        </div>
    </div>

    {{-- ⚙️ Filters --}}
    <div style="position:relative;">
        <button id="filterBtn" class="btn btn-light">⚙️ Фильтр</button>
        <div id="filterPanel"
             style="display:none;position:absolute;top:45px;width:280px;
             background:#fff;border-radius:12px;padding:12px;
             box-shadow:0 10px 30px rgba(0,0,0,.15);z-index:999;">
            <form id="filtersForm">
                <label>
                    <input type="checkbox" name="only_booked" value="1"
                        {{ request('only_booked') ? 'checked' : '' }}>
                    Только занятые
                </label>

                <select name="room_type" class="form-control">
                    <option value="">Все типы</option>
                    @foreach($roomTypes as $type)
                        <option value="{{ $type }}" {{ request('room_type')==$type?'selected':'' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>

                <select name="source" class="form-control">
                    <option value="">Все источники</option>
                    <option value="site">Сайт</option>
                    <option value="booking">Booking</option>
                    <option value="kaspi">Kaspi</option>
                </select>

                <select name="payment_status" class="form-control">
                    <option value="">Оплата</option>
                    <option value="paid">Оплачено</option>
                    <option value="unpaid">Не оплачено</option>
                </select>

                <button class="btn btn-primary btn-block">Применить</button>
            </form>
        </div>
    </div>
</div>

{{-- ===== КАЛЕНДАРЬ ===== --}}
<div class="calendar-wrapper">
    <div class="calendar-header">
        <div class="room-col"><b>Номер</b></div>
        @foreach($dates as $date)
            <div class="day-col">
                <b>{{ $date->format('d') }}</b><br>
                <small>{{ $date->translatedFormat('dd') }}</small>
            </div>
        @endforeach
    </div>

    @foreach($rooms as $room)
        @php $roomBookings = $bookings->where('room_id', $room->id); @endphp
        <div class="calendar-row">
            <div class="room-col">
                <b>{{ $room->number ?? $room->title }}</b><br>
                <small>{{ $room->hotel->title }}</small>
            </div>

            <div class="row-body" style="min-width:{{ $dates->count()*60 }}px">
                @foreach($dates as $i => $date)
                    <div class="day-bg" style="left:{{ $i*60 }}px;width:60px"></div>
                @endforeach

                @foreach($roomBookings as $booking)
                    @php
                        $start = $dates->search(fn($d)=>$d->gte($booking->start_date));
                        $end   = $dates->search(fn($d)=>$d->gte($booking->end_date));
                        $left  = max(0,$start)*60;
                        $width = max(60,($end-$start)*60);
                    @endphp

                    <div class="booking-bar"
                         style="left:{{ $left }}px;width:{{ $width }}px;background:{{ $booking->color }}">
                        {{ $booking->full_name }}
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

@vite(['resources/js/app.js'])
<script>
/* ===== UI LOGIC ===== */
const toggle = (btn,panel)=>{
    btn.onclick=e=>{
        e.stopPropagation();
        panel.style.display=panel.style.display==='block'?'none':'block';
    };
    document.addEventListener('click',()=>panel.style.display='none');
};

toggle(filterBtn,filterPanel);
toggle(legendBtn,legendPanel);
toggle(notificationBell,notificationPanel);

filtersForm.onsubmit=e=>{
    e.preventDefault();
    location.search=new URLSearchParams(new FormData(filtersForm)).toString();
};
</script>
@endsection