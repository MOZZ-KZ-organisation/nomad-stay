@extends('voyager::master')

@section('content')
<style>
.page-wrap {
    max-width: 1100px;
    margin: 20px auto;
}

.header-card {
    background: #fff;
    border-radius: 14px;
    padding: 18px 22px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    margin-bottom: 18px;
}

.title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 6px;
}

.badge-success { background: #dcfce7; color: #166534; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-gray { background: #f3f4f6; color: #374151; }

.grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 18px;
}

.card {
    background: #fff;
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    margin-bottom: 16px;
}

.card h3 {
    font-size: 14px;
    margin-bottom: 12px;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.row-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}

.label-box {
    padding: 10px;
    border-radius: 10px;
    background: #f9fafb;
}

.label-title {
    font-size: 12px;
    color: #6b7280;
}

.label-value {
    font-weight: 600;
    margin-top: 4px;
}

.small {
    font-size: 13px;
    color: #6b7280;
}

hr.soft {
    border: none;
    height: 1px;
    background: #eef2f7;
    margin: 14px 0;
}

.guest-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #4f46e5;
    color: #fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}
</style>
@php
function icon($name) {
    return match($name) {
        'phone' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.12.86.32 1.7.59 2.5a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.58-1.11a2 2 0 0 1 2.11-.45c.8.27 1.64.47 2.5.59A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/></svg>',
        'mail' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="2"/><path d="m4 6 8 6 8-6" stroke="currentColor" stroke-width="2"/></svg>',
        'users' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>',
        'bed' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M3 7v14M21 7v14M3 13h18M7 13V9h10v4" stroke="currentColor" stroke-width="2"/></svg>',
        'bath' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M7 10V7a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="2"/><path d="M5 10h14v7a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4z" stroke="currentColor" stroke-width="2"/></svg>',
        'money' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M9 12h6M12 9v6" stroke="currentColor" stroke-width="2"/></svg>',
        'calendar' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M7 3v3M17 3v3M4 8h16M5 5h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2"/></svg>',
        default => ''
    };
}
@endphp
@php
    /** @var \App\Models\Booking $booking */
    $booking = $dataTypeContent;

    $booking->load(['room', 'hotel']);

    $nights = $booking->start_date->diffInDays($booking->end_date);

    function formatPrice($price) {
        return number_format($price, 0, '.', ' ') . ' ₸';
    }

    $statusConfig = [
        'booked' => ['label' => 'Забронировано', 'class' => 'label-warning'],
        'checked_in' => ['label' => 'Заселён', 'class' => 'label-success'],
        'checked_out' => ['label' => 'Выселен', 'class' => 'label-default'],
        'cancelled' => ['label' => 'Отменено', 'class' => 'label-danger'],
    ];

    $status = $statusConfig[$booking->status] ?? $statusConfig['booked'];
@endphp

<div class="page-wrap">

    {{-- HEADER --}}
    <div class="header-card">
        <div class="title-row">
            <div>
                <h2 style="margin:0;">Бронь #{{ $booking->id }}</h2>
                <div class="small">
                    {{ $booking->source }} / {{ $booking->type }}
                </div>
            </div>

            <div>
                <span class="badge {{ $status['class'] }}">
                    {{ $status['label'] }}
                </span>

                <span class="badge {{ $booking->is_paid ? 'badge-success' : 'badge-danger' }}">
                    {{ $booking->is_paid ? 'Оплачено' : 'Не оплачено' }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid">

        {{-- LEFT --}}
        <div>

            {{-- DATES --}}
            <div class="card">
                <h3>Даты</h3>

                <div class="row-4">
                    <div class="label-box">
                        <div class="label-title">Заезд</div>
                        <div class="label-value">{{ $booking->start_date->format('d.m.Y') }}</div>
                        <div class="small">{{ $booking->arrival_time }}</div>
                    </div>

                    <div class="label-box">
                        <div class="label-title">Выезд</div>
                        <div class="label-value">{{ $booking->end_date->format('d.m.Y') }}</div>
                    </div>

                    <div class="label-box">
                        <div class="label-title">Ночей</div>
                        <div class="label-value">{{ $nights }}</div>
                    </div>

                    <div class="label-box">
                        <div class="label-title">Гостей</div>
                        <div class="label-value">{{ $booking->guests }}</div>
                    </div>
                </div>
            </div>

            {{-- GUEST --}}
            <div class="card">
                <h3>Гость</h3>

                <div style="display:flex; gap:12px; align-items:center;">
                    <div class="guest-avatar">
                        {{ mb_substr($booking->first_name,0,1) }}{{ mb_substr($booking->last_name,0,1) }}
                    </div>

                    <div>
                        <div style="font-weight:600;">
                            {{ $booking->full_name }}
                        </div>
                        <div class="small">
                            {{ $booking->country }}
                        </div>
                    </div>
                </div>

                <hr class="soft">

                <div class="small" style="display:flex;align-items:center;gap:6px;">
                    {!! icon('phone') !!}
                    {{ $booking->phone }}
                </div>

                <div class="small" style="display:flex;align-items:center;gap:6px;">
                    {!! icon('mail') !!}
                    {{ $booking->email }}
                </div>

                @if($booking->is_business_trip)
                    <span class="badge badge-gray" style="margin-top:10px;">
                        Командировка
                    </span>
                @endif
            </div>

            {{-- ROOM --}}
            <div class="card">
                <h3>Номер</h3>

                <div style="font-weight:600;">{{ $booking->room->title }}</div>
                <div class="small">{{ $booking->hotel->title }}</div>
                <div class="small">{{ $booking->hotel->address }}</div>

                <hr class="soft">

                <div class="small" style="display:flex;flex-direction:column;gap:6px;margin-top:10px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        {!! icon('users') !!}
                        {{ $booking->room->capacity }} гостей
                    </div>

                    <div style="display:flex;align-items:center;gap:6px;">
                        {!! icon('bed') !!}
                        {{ $booking->room->beds }} кровати
                    </div>

                    <div style="display:flex;align-items:center;gap:6px;">
                        {!! icon('bath') !!}
                        {{ $booking->room->bathrooms }} ванная
                    </div>

                    <div style="display:flex;align-items:center;gap:6px;">
                        {!! icon('money') !!}
                        {{ number_format($booking->room->price,0,'',' ') }} ₸ / ночь
                    </div>
                </div>
            </div>

            @if($booking->special_requests)
            <div class="card">
                <h3>Пожелания</h3>
                <div class="small">{{ $booking->special_requests }}</div>
            </div>
            @endif

        </div>

        {{-- RIGHT --}}
        <div>

            {{-- PAYMENT --}}
            <div class="card">
                <h3>Оплата</h3>

                <div class="small">Проживание</div>
                <div style="font-weight:600;">
                    {{ formatPrice($booking->price_for_period) }}
                </div>

                <div class="small" style="margin-top:10px;">Налог</div>
                <div style="font-weight:600;">
                    {{ formatPrice($booking->tax) }}
                </div>

                <hr class="soft">

                <div style="font-size:18px;font-weight:700;">
                    {{ formatPrice($booking->total_price) }}
                </div>
            </div>

        </div>

    </div>
</div>
@endsection