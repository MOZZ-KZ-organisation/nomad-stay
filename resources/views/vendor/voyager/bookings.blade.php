@extends('voyager::master')
@section('page_title', 'Календарь бронирований')
@section('page_header')
    <h1 class="page-title">
        <i class="voyager-calendar"></i> Календарь бронирований
    </h1>
@stop
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
.header-actions {
    display:flex;
    gap:16px;
    align-items:center;
}
.icon-btn {
    position:relative;
    cursor:pointer;
}
.badge {
    position:absolute;
    top:-6px;
    right:-6px;
    background:red;
    color:white;
    font-size:11px;
    padding:2px 6px;
    border-radius:50%;
    display:none;
}
.panel {
    position:absolute;
    right:0;
    top:40px;
    background:white;
    border:1px solid #ddd;
    width:320px;
    display:none;
    z-index:999;
    box-shadow:0 4px 10px rgba(0,0,0,.1);
}
.panel h5 {
    padding:10px;
    margin:0;
    border-bottom:1px solid #eee;
}
.panel-body {
    max-height:300px;
    overflow:auto;
}
.filter-panel {
    width:360px;
    padding:12px;
}
.legend-item {
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:6px;
}
.legend-color {
    width:14px;
    height:14px;
    border-radius:3px;
}
</style>

<div class="container-fluid">

    {{-- ===== HEADER ACTIONS ===== --}}
    <div class="header-actions">

        {{-- 🔔 Уведомления --}}
        <div class="icon-btn" id="notificationBell">
            <i class="voyager-bell"></i>
            <span class="badge" id="notificationCount"></span>

            <div class="panel" id="notificationPanel">
                <h5>Уведомления</h5>
                <div class="panel-body" id="notificationsList"></div>
            </div>
        </div>

        {{-- 🛈 Легенда --}}
        <div class="icon-btn" id="legendBtn">
            <i class="voyager-info-circled"></i>

            <div class="panel" id="legendPanel">
                <h5>Легенда</h5>
                <div class="panel-body">
                    <div class="legend-item">
                        <div class="legend-color" style="background:#FACC15"></div>
                        Забронировано
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background:#22C55E"></div>
                        Заселён
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background:#9CA3AF"></div>
                        Выселено
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background:#EF4444"></div>
                        Отменено
                    </div>
                </div>
            </div>
        </div>

        {{-- ⚙️ Фильтр --}}
        <div class="icon-btn" id="filterBtn">
            <i class="voyager-filter"></i>

            <div class="panel filter-panel" id="filterPanel">
                <h5>Фильтр</h5>
                <form id="filtersForm">
                    <div class="form-group">
                        <label>Отель</label>
                        <select name="hotel_id" class="form-control">
                            <option value="">Все</option>
                            @foreach($hotels as $hotel)
                                <option value="{{ $hotel->id }}">{{ $hotel->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Тип комнаты</label>
                        <select name="room_type" class="form-control">
                            <option value="">Все</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="only_booked" value="1">
                            Только забронированные
                        </label>
                    </div>

                    <button class="btn btn-primary btn-block">
                        Применить
                    </button>
                </form>
            </div>
        </div>

    </div>

    {{-- ===== КАЛЕНДАРЬ (заглушка под твой grid) ===== --}}
    <div style="margin-top:20px">
        <p><b>Комнат:</b> {{ count($rooms) }}</p>
        <p><b>Дней:</b> {{ count($dates) }}</p>
        {{-- тут твой grid --}}
    </div>

</div>

{{-- ================= JS ================= --}}
<script>
document.addEventListener('DOMContentLoaded', async () => {

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    /* 🔔 УВЕДОМЛЕНИЯ */
    const bell = document.getElementById('notificationBell');
    const panel = document.getElementById('notificationPanel');
    const list = document.getElementById('notificationsList');
    const count = document.getElementById('notificationCount');

    function render(n) {
        return `
            <div style="padding:8px;border-bottom:1px solid #eee">
                <b>${n.title}</b>
                ${n.booking_id ? '№ ' + n.booking_id : ''}
                <div style="font-size:12px;color:#777">
                    ${new Date(n.created_at).toLocaleString('ru-RU')}
                </div>
            </div>
        `;
    }

    async function loadNotifications() {
        const res = await fetch('/admin/notifications');
        const data = await res.json();

        list.innerHTML = '';
        let unread = 0;

        data.forEach(n => {
            list.innerHTML += render(n);
            if (!n.is_read) unread++;
        });

        if (unread) {
            count.innerText = unread;
            count.style.display = 'inline-block';
        }
    }

    await loadNotifications();

    bell.addEventListener('click', async e => {
        e.stopPropagation();
        panel.style.display = panel.style.display === 'block' ? 'none' : 'block';

        if (panel.style.display === 'block') {
            await fetch('/admin/notifications/mark-read', {
                method:'POST',
                headers:{'X-CSRF-TOKEN':csrf}
            });
            count.style.display = 'none';
        }
    });

    if (window.Echo) {
        Echo.channel('admin.notifications')
            .listen('.new.notification', e => {
                list.innerHTML = render(e.notification) + list.innerHTML;
                count.innerText = (parseInt(count.innerText || 0) + 1);
                count.style.display = 'inline-block';
            });
    }

    /* 🛈 ЛЕГЕНДА */
    const legendBtn = document.getElementById('legendBtn');
    const legendPanel = document.getElementById('legendPanel');
    legendBtn.addEventListener('click', e => {
        e.stopPropagation();
        legendPanel.style.display =
            legendPanel.style.display === 'block' ? 'none' : 'block';
    });

    /* ⚙️ ФИЛЬТР */
    const filterBtn = document.getElementById('filterBtn');
    const filterPanel = document.getElementById('filterPanel');
    const filtersForm = document.getElementById('filtersForm');

    filterBtn.addEventListener('click', e => {
        e.stopPropagation();
        filterPanel.style.display =
            filterPanel.style.display === 'block' ? 'none' : 'block';
    });

    filtersForm.addEventListener('click', e => e.stopPropagation());

    filtersForm.addEventListener('submit', e => {
        e.preventDefault();
        const params = new URLSearchParams(new FormData(filtersForm)).toString();
        window.location = '?' + params;
    });

    /* ❌ Закрытие */
    document.addEventListener('click', () => {
        panel.style.display = 'none';
        legendPanel.style.display = 'none';
        filterPanel.style.display = 'none';
    });
});
</script>
@endsection