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
    border: 1px solid #e5e7eb;
    border-bottom: none;
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
    height: 60px;
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    border-left: 1px solid #e5e7eb; 
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
.notifications-wrapper {
    position: relative;
}
.icon-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    position: relative;
    font-size: 16px;
}
#notificationCount {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ef4444;
    color: #fff;
    border-radius: 999px;
    font-size: 11px;
    padding: 2px 6px;
    display: none;
    line-height: 1;
}
.dropdown-panel {
    display: none;
    position: absolute;
    top: 48px;
    right: 0;
    width: 320px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,.12);
    padding: 12px;
    z-index: 9999;
}
.dropdown-panel.show {
    display: block;
}
.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.panel-header button {
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 14px;
}
.notify-item {
    padding: 8px 6px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}
.notify-item:last-child {
    border-bottom: none;
}
.legend-wrapper {
    position: relative;
}
.legend {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    display: inline-block;
    margin-right: 6px;
}
.legend.booked {
    background: #facc15;
}
.legend.checked-in {
    background: #4ade80;
}
.legend.checked-out {
    background: #9ca3af;
}
.nav-arrow {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    text-decoration: none;
    font-weight: bold;
    font-size: 18px;
    color: #111;
    cursor: pointer;
    transition: all 0.2s ease;
}
.nav-arrow:hover {
    background: #3e72e2;
    color: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
<div class="top-controls" style="display:flex;align-items:center;gap:12px;margin:16px; flex-wrap:wrap;">
    <h1 style="font-size:26px;margin-right:12px;">
        Календарь бронирований
        <span style="font-size:14px;color:#6b7280;">
            {{ $dates->first()->format('d.m') }} – {{ $dates->last()->format('d.m') }}
        </span>
    </h1>
    <div class="legend-wrapper" style="margin-left:12px;">
        <button id="legendBtn" class="icon-btn">🛈</button>
        <div id="legendPanel" class="dropdown-panel">
            <b>Легенда</b>
            <div><span class="legend booked"></span> Забронировано</div>
            <div><span class="legend checked-in"></span> Заселено</div>
            <div><span class="legend checked-out"></span> Выселено</div>
        </div>
    </div>
    <div class="notifications-wrapper" style="margin-left:12px;">
        <button id="notificationBell" class="icon-btn">🔔
            <span id="notificationCount"></span>
        </button>
        <div id="notificationPanel" class="dropdown-panel">
            <div class="panel-header">
                <b>Уведомления</b>
                <button id="closeNotifications">✖</button>
            </div>
            <div id="notificationsList"></div>
        </div>
    </div>
    <div class="filters-wrapper" style="position:relative;margin-left:12px;">
        <button id="filterBtn" class="btn btn-default">⚙️ Фильтр</button>
        <div id="filterPanel" class="filter-panel">
            <form id="filtersForm">
                <label>
                    <input type="checkbox" name="only_booked" value="1" {{ request('only_booked') ? 'checked' : '' }}>
                    Только занятые
                </label>
                <div style="margin-top:10px;">
                    <label>Тип номера</label>
                    <select name="room_type" class="form-control">
                        <option value="">Все</option>
                        @foreach($roomTypes as $type)
                            <option value="{{ $type }}" {{ request('room_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary btn-block" style="margin-top:12px;">Применить</button>
            </form>
        </div>
    </div>
    <div class="calendar-nav" style="margin-left:auto; display:flex; gap:6px;">
        <a class="nav-arrow" 
        href="{{ route('admin.bookings.calendar', array_merge(request()->all(), ['start' => $startDate->copy()->subDay()->toDateString()])) }}">
        ←
        </a>
        <a class="nav-arrow" 
        href="{{ route('admin.bookings.calendar', array_merge(request()->all(), ['start' => $startDate->copy()->addDay()->toDateString()])) }}">
        →
        </a>
    </div>
</div>
<div class="calendar-wrapper" style="overflow:hidden;">
    <div class="calendar-header">
        <div class="room-col"><b>Номер</b></div>
        @foreach($dates as $date)
            <div class="day-col" style="border-right:1px solid #e5e7eb;">
                <b style="font-size:14px;">{{ $date->format('d') }}</b>
                <div style="font-size:11px;color:#6b7280;">{{ $date->translatedFormat('dd') }}</div>
            </div>
        @endforeach
    </div>
    @foreach($rooms as $room)
        <div class="calendar-row" style="border-bottom:1px solid #eee;">
            <div class="room-col">
                <b>{{ $room->number ?? $room->title }}</b>
                <div class="room-hotel" style="font-size:11px;color:#6b7280;">{{ $room->hotel->title }}</div>
            </div>
            <div class="row-body" style="position:relative; height:64px;">
                @foreach($dates as $i => $date)
                    <div class="day-bg"
                         style="left:{{ ($i / 18) * 100 }}%; width:{{ 100 / 18 }}%; border-right:1px solid #e5e7eb;">
                    </div>
                @endforeach
                @foreach($bookings->where('room_id', $room->id) as $booking)
                    @php
                        $start = $dates->search(fn($d) => $d->gte($booking->start_date));
                        $end   = $dates->search(fn($d) => $d->gte($booking->end_date));
                        $start = max(0, $start) + 0.5;
                        $end   = ($end ?? 18) + 0.5;
                        $left  = ($start / 18) * 100;
                        $width = (($end - $start) / 18) * 100;
                    @endphp
                    <div class="booking-bar"
                         style="left:{{ $left }}%; width:{{ $width }}%; background:{{ $booking->color }};">
                        {{ $booking->full_name }}
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const bell   = notificationBell;
    const panel  = notificationPanel;
    const count  = notificationCount;
    const list   = notificationsList;
    async function loadNotifications() {
        const res = await fetch('/admin/notifications');
        const data = await res.json();
        let unread = 0;
        list.innerHTML = '';
        data.forEach(n => {
            if(!n.is_read) unread++;
            list.innerHTML += `<div class="notify-item">${n.title}</div>`;
        });
        count.textContent = unread || '';
        count.style.display = unread ? 'block' : 'none';
    }
    loadNotifications();
    bell.onclick = async e => {
        e.stopPropagation();
        panel.classList.toggle('show');
        if(panel.classList.contains('show')){
            await fetch('/admin/notifications/mark-read', {
                method:'POST',
                headers:{'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content}
            });
            count.style.display='none';
        }
    };
    legendBtn.onclick = e => { e.stopPropagation(); legendPanel.classList.toggle('show'); };
    filterBtn.onclick = e => { e.stopPropagation(); filterPanel.classList.toggle('show'); };
    filtersForm.onclick = e => e.stopPropagation();
    filtersForm.onsubmit = e => {
        e.preventDefault();
        const params = new URLSearchParams(new FormData(filtersForm)).toString();
        location.search = params;
    };
    document.onclick = () => {
        panel.classList.remove('show');
        legendPanel.classList.remove('show');
        filterPanel.classList.remove('show');
    };
});
</script>
@endsection