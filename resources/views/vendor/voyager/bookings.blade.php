@extends('voyager::master')
@section('content')
<style>
.top-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 16px;
    flex-wrap: wrap;
}
.calendar-wrapper {
    background: #fff;
    border-radius: 2px;
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
    background: #ffffff;
    flex-shrink: 0;
    border-right: 1px solid #ededed;
}
.day-col {
    flex: 1;
    text-align: center;
    padding: 6px 0;
    font-size: 12px;
    border-right: 1px solid #ededed;
}
.day-week {
    font-size: 11px;
    color: #6b7280;
}
.calendar-row {
    height: 38px;
    border-top: 1px solid #e5e7eb;
}
.row-body {
    position: relative;
    flex: 1;
    height: 38px;
}
.day-bg {
    position: absolute;
    top: 0;
    bottom: 0;
    background: #ffffff;
    border-right: 1px solid #e5e7eb;
}
.booking-bar {
    position: absolute;
    top: 0;
    bottom: 0;
    border-radius: 2px;
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
    transition: filter 0.2s ease;
    border-left: 2px solid #fff;
    border-right: 2px solid #fff;
}
.booking-bar:hover {
    filter: brightness(95%);
}

.booking-tooltip {
    position: fixed;
    width: 360px;
    background: #f3f3f3;
    border: 1px solid #d8d8d8;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.14);
    z-index: 99999;
    overflow: hidden;
    display: none;
    font-family: Arial, sans-serif;
}

.booking-tooltip-body {
    padding: 14px;
}

.booking-tooltip-room {
    font-size: 18px;
    font-weight: 700;
    color: #222;
    line-height: 1;
    margin-bottom: 10px;
}

.booking-tooltip-meta-row {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 6px;
}
.booking-tooltip-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.booking-tooltip-dates {
    font-size: 15px;
    font-weight: 700;
    color: #222;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.booking-tooltip-meta {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #222;
    font-weight: 600;
}

.booking-tooltip-paid {
    font-size: 14px;
    font-weight: 700;
    color: #111;
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    flex-shrink: 0;
}

.booking-tooltip-times {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
    color: #8a8a8a;
    font-size: 13px;
}

.booking-tooltip-time {
    display: flex;
    align-items: center;
    gap: 5px;
}

.booking-tooltip-line {
    color: #737373;
    font-size: 14px;
    margin-bottom: 6px;
}

.booking-tooltip-line b {
    color: #5b5b5b;
    font-weight: 600;
}

.booking-icon {
    width: 14px;
    height: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.booking-icon svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
}
.booking-icon {
    width: 14px;
    height: 14px;
    display: inline-flex;
}

.booking-icon svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
}
.hover-slot {
    position: absolute;
    top: 0;
    bottom: 0;
    background: transparent;
    z-index: 10;
    cursor: pointer;
    transition: background 0.15s ease;
}
.hover-slot:hover {
    background: #d8efff;
}
.filters-wrapper { position: relative; }
.filter-panel {
    display: none;
    position: absolute;
    top: 45px;
    width: 280px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    padding: 15px;
    z-index: 999;
}
.room-hotel { font-size: 11px; color: #6b7280; }
.notifications-wrapper { position: relative; }
.icon-btn {
    width: 40px; height: 40px; border-radius: 50%; border:1px solid #e5e7eb;
    background: #fff; cursor: pointer; position: relative; font-size: 16px;
}
#notificationCount {
    position: absolute; top: -6px; right: -6px;
    background: #ef4444; color: #fff; border-radius: 999px;
    font-size: 11px; padding: 2px 6px; display: none; line-height: 1;
}
.dropdown-panel {
    display: none; position: absolute; top: 48px; right: 0;
    width: 320px; background: #fff; border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,.12); padding: 12px; z-index: 9999;
}
.dropdown-panel.show { display: block; }
.panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.panel-header button { border: none; background: transparent; cursor: pointer; font-size: 14px; }
.notify-item { padding: 8px 6px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
.notify-item:last-child { border-bottom: none; }
.legend-wrapper { position: relative; }
.legend { width: 14px; height: 14px; border-radius: 4px; display: inline-block; margin-right: 6px; }
.legend.booked { background: #facc15; }
.legend.checked-in { background: #4ade80; }
.legend.checked-out { background: #9ca3af; }
.nav-arrow {
    display: flex; justify-content: center; align-items: center;
    width: 36px; height: 36px; border-radius: 8px;
    background: #fff; border:1px solid #e6e6e6;
    text-decoration:none; font-weight:bold; font-size:18px; color:#000;
    cursor:pointer; transition: all 0.2s ease;
}
.nav-arrow:hover { background:#f3f3f3; color:#000; box-shadow:0 4px 12px rgba(0,0,0,0.15); }
.day-col.weekend {
    background: #ffe7e7;
}
.day-col.weekend b {
    color: #dc2626;
}
.day-col.weekend .day-week {
    color: #ef4444;
}
.day-bg.weekend {
    background: #fff3f3;
}
</style>
<div class="top-controls">
    <h1 style="font-size:26px;">
        Календарь бронирований
        <span style="font-size:14px;color:#6b7280;">
            {{ $dates->first()->format('d.m') }} – {{ $dates->last()->format('d.m') }}
        </span>
    </h1>
    <div class="legend-wrapper">
        <button id="legendBtn" class="icon-btn">🛈</button>
        <div id="legendPanel" class="dropdown-panel">
            <b>Легенда</b>
            <div><span class="legend booked"></span> Забронировано</div>
            <div><span class="legend checked-in"></span> Заселено</div>
            <div><span class="legend checked-out"></span> Выселено</div>
        </div>
    </div>
    <div class="notifications-wrapper">
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
    <div class="filters-wrapper">
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
                            <option value="{{ $type }}" {{ request('room_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary btn-block" style="margin-top:12px;">Применить</button>
            </form>
        </div>
    </div>
    <div class="calendar-nav" style="margin-left:auto; display:flex; gap:6px;">
        <a class="nav-arrow" href="{{ route('admin.bookings.calendar', array_merge(request()->all(), ['start' => $startDate->copy()->subDay()->toDateString()])) }}">←</a>
        <a class="nav-arrow" href="{{ route('admin.bookings.calendar', array_merge(request()->all(), ['start' => $startDate->copy()->addDay()->toDateString()])) }}">→</a>
    </div>
</div>
<div class="calendar-wrapper">
    <div class="calendar-header">
        <div class="room-col"><b>Номер</b></div>
        @foreach($dates as $date)
            <div class="day-col {{ $date->isWeekend() ? 'weekend' : '' }}">
                <b>{{ $date->format('d') }}</b>
                <div class="day-week">
                    {{ mb_substr($date->translatedFormat('D'), 0, 2) }}
                </div>
            </div>
        @endforeach
    </div>
    @foreach($rooms as $room)
        <div class="calendar-row">
            <div class="room-col">
                <b>{{ $room->number ?? $room->title }}</b>
                <div class="room-hotel">{{ $room->hotel->title }}</div>
            </div>
            <div class="row-body">
                @foreach($dates as $i => $date)
                    <div class="day-bg {{ $date->isWeekend() ? 'weekend' : '' }}"
                        style="left:{{ ($i/18)*100 }}%; width:{{ 100/18 }}%;">
                    </div>
                @endforeach
                @for($i = 0; $i < 18-1; $i++)
                    <div class="hover-slot" style="left:{{ (($i+0.5)/18)*100 }}%; width:{{ (1/18)*100 }}%;"></div>
                @endfor
                @foreach($bookings->where('room_id', $room->id) as $booking)
                    @php
                        $start = $dates->search(fn($d) => $d->gte($booking->start_date));
                        $end = $dates->search(fn($d) => $d->gte($booking->end_date));
                        $start = max(0,$start) + 0.5;
                        $end = ($end ?? 18) + 0.5;
                        $left = ($start/18)*100;
                        $width = (($end-$start)/18)*100;
                    @endphp
                    <div
                        class="booking-bar"
                        data-url="{{ route('voyager.bookings.show', $booking->id) }}"
                        style="cursor:pointer; left:{{ $left }}%; width:{{ $width }}%; background:{{ $booking->color }};"
                        
                        data-id="{{ $booking->id }}"
                        data-guest="{{ $booking->full_name }}"
                        data-phone="{{ $booking->phone }}"
                        data-start="{{ \Carbon\Carbon::parse($booking->start_date)->format('d.m.Y') }}"
                        data-end="{{ \Carbon\Carbon::parse($booking->end_date)->format('d.m.Y') }}"
                        data-status="{{ $booking->status }}"
                        data-total="{{ number_format($booking->total_price, 0, '.', ' ') }} ₸"
                        data-paid="{{ $booking->is_paid ? 'Оплачено' : 'Не оплачено' }}"
                        data-room="{{ $room->number ?? $room->title }}"
                        data-hotel="{{ $room->hotel->title }}"
                        data-source="{{ $booking->source }}"
                        data-guests="{{ $booking->guests }}"
                        data-nights="{{ \Carbon\Carbon::parse($booking->start_date)->diffInDays($booking->end_date) }}"
                    >
                        {{ $booking->full_name }}
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
<div id="bookingTooltip" class="booking-tooltip"></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const bell = document.getElementById('notificationBell');
    const panel = document.getElementById('notificationPanel');
    const count = document.getElementById('notificationCount');
    const list = document.getElementById('notificationsList');
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
            await fetch('/admin/notifications/mark-read',{
                method:'POST',
                headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}
            });
            count.style.display='none';
        }
    };
    document.getElementById('legendBtn').onclick = e => { e.stopPropagation(); document.getElementById('legendPanel').classList.toggle('show'); };
    document.getElementById('filterBtn').onclick = e => { e.stopPropagation(); document.getElementById('filterPanel').classList.toggle('show'); };
    document.getElementById('filtersForm').onclick = e => e.stopPropagation();
    document.getElementById('filtersForm').onsubmit = e => {
        e.preventDefault();
        const params = new URLSearchParams(new FormData(document.getElementById('filtersForm'))).toString();
        location.search = params;
    };
    document.onclick = () => {
        panel.classList.remove('show');
        document.getElementById('legendPanel').classList.remove('show');
        document.getElementById('filterPanel').classList.remove('show');
    };
});

const tooltip = document.getElementById('bookingTooltip');
document.querySelectorAll('.booking-bar').forEach(bar => {
    bar.addEventListener('click', () => {
        window.location.href = bar.dataset.url;
    });
    bar.addEventListener('mouseenter', () => {

        tooltip.innerHTML = `
            <div class="booking-tooltip-body">

                <div class="booking-tooltip-room">
                    ${bar.dataset.room}
                </div>

                <div class="booking-tooltip-top">

                    <div class="booking-tooltip-dates">

                        <span>
                            ${bar.dataset.start} — ${bar.dataset.end}
                        </span>

                        <div class="booking-tooltip-meta-row">

                            <span class="booking-tooltip-meta">

                                <span class="booking-icon">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M21 12.79A9 9 0 0 1 11.21 3c0-.34.02-.67.05-1A1 1 0 0 0 10 1a10 10 0 1 0 13 13 1 1 0 0 0-1.21-1.21c-.33.03-.66.05-1 .05Z"/>
                                    </svg>
                                </span>

                                ${bar.dataset.nights}
                            </span>

                            <span class="booking-tooltip-meta">

                                <span class="booking-icon">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 1.79-8 4v2h16v-2c0-2.21-3.58-4-8-4Z"/>
                                    </svg>
                                </span>

                                ${bar.dataset.guests}
                            </span>

                        </div>

                    </div>

                    <div class="booking-tooltip-paid">
                        ${
                            bar.dataset.paid === 'Оплачено'
                            ? `
                                <span class="booking-icon">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M9 16.2 4.8 12 3.4 13.4 9 19l12-12-1.4-1.4z"/>
                                    </svg>
                                </span>
                            `
                            : ``
                        }
                        ${bar.dataset.paid}
                    </div>

                </div>

                <div class="booking-tooltip-times">

                    <div class="booking-tooltip-time">

                        <span class="booking-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M7 3v2H5a2 2 0 0 0-2 2v3h18V7a2 2 0 0 0-2-2h-2V3h-2v2H9V3H7Zm14 9H3v5a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5Z"/>
                            </svg>
                        </span>

                        14:00
                    </div>

                    <div class="booking-tooltip-time">

                        <span class="booking-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M17 3v2h2a2 2 0 0 1 2 2v3H3V7a2 2 0 0 1 2-2h2V3h2v2h6V3h2Zm4 9v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5h18Zm-5 2h-4v2h4v-2Z"/>
                            </svg>
                        </span>

                        12:00
                    </div>

                </div>

                <div class="booking-tooltip-line">
                    <b>Гость:</b> ${bar.dataset.guest}
                </div>

            </div>
        `;

        tooltip.style.display = 'block';
    });

    bar.addEventListener('mousemove', e => {

        tooltip.style.left = (e.clientX + 18) + 'px';
        tooltip.style.top = (e.clientY + 18) + 'px';
    });

    bar.addEventListener('mouseleave', () => {
        tooltip.style.display = 'none';
    });

});
</script>
@endsection