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
<div class="notifications-wrapper" style="position:relative; margin:1rem;">
    <button id="notificationBell" style="
        background:#fff;
        border-radius:50%;
        width:40px;
        height:40px;
        border:1px solid #ddd;
        cursor:pointer;
        position:relative;
    ">
        🔔
        <span id="notificationCount" style="
            position:absolute;
            top:-6px;
            right:-6px;
            background:red;
            color:#fff;
            border-radius:50%;
            font-size:11px;
            padding:2px 6px;
            display:none;
        ">0</span>
    </button>
    <div id="notificationPanel" style="
        display:none;
        position:absolute;
        right:0;
        top:50px;
        width:320px;
        background:#fff;
        border-radius:10px;
        box-shadow:0 5px 20px rgba(0,0,0,0.08);
        padding:10px;
        z-index:9999;
    ">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <h5>Уведомления</h5>
            <button id="closeNotifications" style="
                background: transparent;
                border: none;
                font-size:16px;
                cursor:pointer;
            ">✖</button>
        </div>
        <div id="notificationsList"></div>
    </div>
</div>
<h1>Брони и заявки</h1>
<div class="filters-wrapper" style="position:relative; display:inline-block; margin:1rem;">
    <button id="filterBtn" style="
        background:#fff;
        border-radius:12px;
        padding:8px 14px;
        border:1px solid #ddd;
        cursor:pointer;
        display:flex;
        align-items:center;
        gap:6px;
    ">
        <span>⚙️</span> Фильтр
    </button>
    <div id="filterPanel" style="
        position:absolute;
        top:45px;
        right:0;
        width:280px;
        background:#fff;
        border-radius:12px;
        box-shadow:0 8px 25px rgba(0,0,0,0.08);
        padding:15px;
        display:none;
        z-index:999;
    ">
        <form id="filtersForm">
            <h5 style="margin-bottom:10px;">Фильтры</h5>
            <label style="display:flex; align-items:center; gap:6px; margin-bottom:10px;">
                <input type="checkbox" name="only_booked" value="1">
                Только занятые
            </label>
            <div style="margin-bottom:10px;">
                <label>Тип номера</label>
                <select name="room_type" style="width:100%;" class="form-control">
                    <option value="">Все</option>
                    @foreach($roomTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>Источник</label>
                <select name="source" class="form-control">
                    <option value="">Все</option>
                    <option value="site">Сайт</option>
                    <option value="booking">Booking.com</option>
                    <option value="kaspi">Kaspi</option>
                </select>
            </div>
            <div style="margin-bottom:15px;">
                <label>Статус</label>
                <select name="payment_status" class="form-control">
                    <option value="">Все</option>
                    <option value="paid">Оплачено</option>
                    <option value="unpaid">Не оплачено</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Применить</button>
        </form>
    </div>
</div>
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
                                {{ number_format($booking->price_per_night, 0, '.', ' ') }} ₸
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
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const bell = document.getElementById('notificationBell');
    const panel = document.getElementById('notificationPanel');
    const list = document.getElementById('notificationsList');
    const count = document.getElementById('notificationCount');
    const closeBtn = document.getElementById('closeNotifications');
    bell.addEventListener('click', () => {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        if (panel.style.display === 'block') {
            await fetch('/admin/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
            });
            count.style.display = 'none';
        }
    });
    closeBtn.addEventListener('click', () => {
        panel.style.display = 'none';
    });
    function notificationTemplate(data) {
        const date = new Date(data.created_at).toLocaleDateString('ru-RU', { day:'numeric', month:'long' });
        return `
            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                padding:10px;
                border-bottom:1px solid #eee;
                cursor:pointer;
                font-size:14px;
                color:#333;
            ">
                <div>
                    ${data.title} ${data.booking_id ? '№ ' + data.booking_id : ''}
                </div>
                <div style="font-size:12px; color:#888;">
                    ${date}
                </div>
            </div>
        `;
    }
    async function loadNotifications(){
        const res = await fetch('/admin/notifications');
        const data = await res.json();
        list.innerHTML = '';
        let unread = 0;
        data.forEach(n => {
            if(!n.is_read) unread++;
            list.innerHTML += notificationTemplate(n);
        });
        if (unread > 0) {
            count.innerText = unread;
            count.style.display = 'inline-block';
        } else {
            count.style.display = 'none';
        }
    }
    await loadNotifications();
    console.log('Echo is:', window.Echo);
    window.Echo.channel('admin.notifications')
        .listen('.new.notification', (e) => {
            console.log('NEW NOTIFICATION', e);
            list.innerHTML =
                notificationTemplate(e.notification) + list.innerHTML;

            let current = parseInt(count.innerText || 0);
            count.innerText = current + 1;
            count.style.display = 'inline-block';
        });
        const filterBtn   = document.getElementById('filterBtn');
        const filterPanel = document.getElementById('filterPanel');
        const filtersForm = document.getElementById('filtersForm');
        filterBtn.addEventListener('click', () => {
            filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
        });
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.filters-wrapper')) {
                filterPanel.style.display = 'none';
            }
        });
        filtersForm.addEventListener('submit', function(e){
            e.preventDefault();
            const params = new URLSearchParams(new FormData(this)).toString();
            window.location.href = window.location.pathname + '?' + params;
        });
});
</script>
@endsection