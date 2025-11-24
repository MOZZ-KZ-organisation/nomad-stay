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
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const bell = document.getElementById('notificationBell');
    const panel = document.getElementById('notificationPanel');
    const list = document.getElementById('notificationsList');
    const count = document.getElementById('notificationCount');
    bell.addEventListener('click', () => {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });
    function notificationTemplate(data) {
        return `
            <div style="
                padding:10px;
                border-bottom:1px solid #eee;
                cursor:pointer;
            ">
                /*
                <div style="font-size:12px; color:#888;">
                    ${data.source ? data.source + ' (' + data.source + ')' : ''}
                </div>
                */
                <strong>${data.title}</strong><br>
                ${data.booking_id ? '№ ' + data.booking_id + '<br>' : ''}
                <small>${new Date(data.created_at).toLocaleDateString('ru-RU', { day:'numeric', month:'long' }) }</small>
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
    window.Echo.channel('admin.notifications')
        .listen('.new.notification', (e) => {
            console.log('NEW NOTIFICATION', e);
            list.innerHTML =
                notificationTemplate(e.notification) + list.innerHTML;

            let current = parseInt(count.innerText || 0);
            count.innerText = current + 1;
            count.style.display = 'inline-block';
        });
    document.getElementById('closeNotifications').onclick = function() {
        panel.style.display = 'none';
        alert('Закрытие сработало!');
    };
});
</script>
@endsection