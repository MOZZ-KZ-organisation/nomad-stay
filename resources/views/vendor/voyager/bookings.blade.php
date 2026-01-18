@extends('voyager::master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ===== TOP CONTROLS ===== */
.top-controls {
    display:flex;
    align-items:center;
    gap:12px;
    margin:1rem;
}

/* ===== CALENDAR (ТВОЙ) ===== */
.calendar-wrapper {
    background:#fff;
    border-radius:12px;
    overflow-x:auto;
    margin:16px;
}
.calendar-header, .calendar-row {
    display:flex;
    position:relative;
}
.room-col {
    width:150px;
    padding:10px;
    border-right:2px solid #e5e7eb;
    background:#fafafa;
    flex-shrink:0;
}
.day-col {
    width:60px;
    border-right:1px solid #e5e7eb;
    text-align:center;
    padding:6px 0;
    font-size:12px;
}
.calendar-row {
    height:64px;
    border-bottom:1px solid #eee;
}
.row-body {
    position:relative;
    flex:1;
    min-height:64px;
}
.day-bg {
    position:absolute;
    top:0;
    bottom:0;
    background:#eff6ff;
    border-right:1px solid #e5e7eb;
}
.booking-bar {
    position:absolute;
    top:6px;
    bottom:6px;
    border-radius:8px;
    padding:4px 8px;
    font-size:12px;
    font-weight:500;
    color:#111;
    display:flex;
    align-items:center;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    cursor:pointer;
    z-index:10;
}
.booking-bar:hover {
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
</style>

{{-- ================= TOP BAR ================= --}}
<div class="top-controls">
    <h1 style="font-size:26px;">Календарь бронирований</h1>

    {{-- 🔔 Уведомления --}}
    <div class="notifications-wrapper" style="position:relative;">
        <button id="notificationBell" style="width:40px;height:40px;border-radius:50%;border:1px solid #ddd;background:#fff;">
            🔔
            <span id="notificationCount"
                  style="position:absolute;top:-6px;right:-6px;background:red;color:#fff;border-radius:50%;font-size:11px;padding:2px 6px;display:none;">
            </span>
        </button>

        <div id="notificationPanel"
             style="display:none;position:absolute;top:50px;width:320px;background:#fff;border-radius:10px;
             box-shadow:0 5px 20px rgba(0,0,0,.08);padding:10px;z-index:9999;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <h5>Уведомления</h5>
                <button id="closeNotifications" style="border:none;background:transparent;">✖</button>
            </div>
            <div id="notificationsList"></div>
        </div>
    </div>

    {{-- 🛈 Легенда --}}
    <div style="position:relative;">
        <button id="legendBtn" style="width:40px;height:40px;border-radius:50%;border:1px solid #ddd;background:#fff;">🛈</button>
        <div id="legendPanel"
             style="display:none;position:absolute;top:45px;width:220px;background:#fff;border-radius:12px;
             box-shadow:0 8px 25px rgba(0,0,0,.08);padding:15px;z-index:999;">
            <h5>Легенда</h5>
            <div><span style="background:#2D9CDB;width:14px;height:14px;display:inline-block"></span> Забронировано</div>
            <div><span style="background:#BDBDBD;width:14px;height:14px;display:inline-block"></span> Выселено</div>
            <div><span style="background:#EB5757;width:14px;height:14px;display:inline-block"></span> Отменено</div>
        </div>
    </div>

    {{-- ⚙️ Фильтр --}}
    <div class="filters-wrapper" style="position:relative;">
        <button id="filterBtn" style="border:1px solid #ddd;border-radius:12px;padding:8px 14px;background:#fff;">
            ⚙️ Фильтр
        </button>
        <div id="filterPanel"
             style="display:none;position:absolute;top:45px;width:280px;background:#fff;border-radius:12px;
             box-shadow:0 8px 25px rgba(0,0,0,.08);padding:15px;z-index:999;">
            <form id="filtersForm">
                <label>
                    <input type="checkbox" name="only_booked" value="1" {{ request('only_booked') ? 'checked' : '' }}>
                    Только занятые
                </label>

                <div style="margin-top:10px">
                    <label>Тип номера</label>
                    <select name="room_type" class="form-control">
                        <option value="">Все</option>
                        @foreach($roomTypes as $type)
                            <option value="{{ $type }}" {{ request('room_type')==$type?'selected':'' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="btn btn-primary btn-block" style="margin-top:12px">Применить</button>
            </form>
        </div>
    </div>
</div>

{{-- ================= CALENDAR ================= --}}
<div class="calendar-wrapper">
    {{-- HEADER --}}
    <div class="calendar-header">
        <div class="room-col"><b>Номер</b></div>
        @foreach($dates as $date)
            <div class="day-col">
                <b>{{ $date->format('d') }}</b>
                <div style="font-size:11px;color:#666">{{ $date->translatedFormat('dd') }}</div>
            </div>
        @endforeach
    </div>

    {{-- ROOMS --}}
    @foreach($rooms as $room)
        @php $roomBookings = $bookings->where('room_id', $room->id); @endphp
        <div class="calendar-row">
            <div class="room-col">
                <b>{{ $room->number ?? $room->title }}</b>
                <div style="font-size:11px;color:#666">{{ $room->hotel->title }}</div>
            </div>

            <div class="row-body" style="min-width:{{ $dates->count()*60 }}px">
                @foreach($dates as $i=>$date)
                    <div class="day-bg" style="left:{{ $i*60 }}px;width:60px"></div>
                @endforeach

                @foreach($roomBookings as $booking)
                    @php
                        $startIndex = $dates->search(fn($d)=>$d->gte($booking->start_date));
                        $endIndex   = $dates->search(fn($d)=>$d->gte($booking->end_date));
                        $left = max(0,$startIndex)*60+30;
                        $width = max(60,($endIndex-$startIndex)*60);
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

{{-- ================= JS (ИЗ ВТОРОГО КОДА) ================= --}}
<script>
document.addEventListener('DOMContentLoaded', async () => {

    /* 🔔 notifications */
    const bell = document.getElementById('notificationBell');
    const panel = document.getElementById('notificationPanel');
    const list  = document.getElementById('notificationsList');
    const count = document.getElementById('notificationCount');

    async function loadNotifications(){
        const res = await fetch('/admin/notifications');
        const data = await res.json();
        let unread = 0;
        list.innerHTML = '';
        data.forEach(n=>{
            if(!n.is_read) unread++;
            list.innerHTML += `<div style="padding:8px;border-bottom:1px solid #eee">${n.title}</div>`;
        });
        if(unread){ count.innerText = unread; count.style.display='inline-block'; }
    }
    await loadNotifications();

    bell.onclick = async (e)=>{
        e.stopPropagation();
        panel.style.display = panel.style.display==='block'?'none':'block';
        if(panel.style.display==='block'){
            await fetch('/admin/notifications/mark-read',{
                method:'POST',
                headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}
            });
            count.style.display='none';
        }
    };

    /* legend */
    legendBtn.onclick = e=>{
        e.stopPropagation();
        legendPanel.style.display = legendPanel.style.display==='block'?'none':'block';
    };

    /* filter */
    filterBtn.onclick = e=>{
        e.stopPropagation();
        filterPanel.style.display = filterPanel.style.display==='block'?'none':'block';
    };
    filtersForm.onclick = e=>e.stopPropagation();
    filtersForm.onsubmit = e=>{
        e.preventDefault();
        const p = new URLSearchParams(new FormData(filtersForm)).toString();
        location.search = p;
    };

    document.onclick = ()=>{
        panel.style.display='none';
        legendPanel.style.display='none';
        filterPanel.style.display='none';
    };
});
</script>
@endsection