@extends('voyager::master')

@section('content')
<style>
.page-wrap {
    max-width: 1100px;
    margin: 24px auto;
}
.page-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin: 10px 10px 18px;
}
.back-btn {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #111;
    text-decoration: none;
    transition: .2s;
}
.back-btn:hover {
    background: #f8fafc;
    transform: translateY(-1px);
}
.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    padding-left: 1rem;
}
.form-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 18px;
}
.card {
    background: #fff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 10px 30px rgba(0,0,0,.05);
    margin-bottom: 18px;
}
.card-title {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 18px;
    color: #111827;
}
.grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}
.grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}
.field {
    display: flex;
    flex-direction: column;
}
.field label {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 7px;
    color: #374151;
}
.field-value {
    padding: 12px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #f9fafb;
    font-size: 14px;
    color: #111827;
    min-height: 46px;
    display: flex;
    align-items: center;
}
.select {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    padding: 12px 14px;
    font-size: 14px;
    transition: .2s;
}
.select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99,102,241,.08);
}
.side-card {
    position: sticky;
    top: 20px;
}
.info-line {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}
.info-line:last-child {
    border-bottom: none;
}
.submit-btn {
    width: 100%;
    height: 52px;
    border: none;
    border-radius: 14px;
    background: #111827;
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}
.submit-btn:hover {
    background: #000;
}
.guest-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #111827;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    flex-shrink: 0;
}
.badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}
.badge-gray { background: #f3f4f6; color: #374151; }
hr.soft {
    border: none;
    height: 1px;
    background: #f1f5f9;
    margin: 14px 0;
}
.small {
    font-size: 13px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
}
@media(max-width:900px) {
    .form-grid { grid-template-columns: 1fr; }
    .grid-4, .grid-2 { grid-template-columns: 1fr 1fr; }
}
</style>

@php
    /** @var \App\Models\Booking $booking */
    $booking = $dataTypeContent;
    $booking->load(['room', 'hotel']);
    $nights = $booking->start_date->diffInDays($booking->end_date);
@endphp

<div class="page-wrap">

    {{-- HEADER --}}
    <div class="page-header">
        <a href="{{ route('voyager.bookings.index') }}" class="back-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2"/>
            </svg>
        </a>
        <div class="page-title">Бронь #{{ $booking->id }}</div>
    </div>

    <div class="form-grid">

        {{-- LEFT --}}
        <div>

            {{-- ДАТЫ --}}
            <div class="card">
                <div class="card-title">Номер и даты</div>
                <div class="grid-4">
                    <div class="field">
                        <label>Номер</label>
                        <div class="field-value">{{ $booking->room->title }}</div>
                    </div>
                    <div class="field">
                        <label>Заезд</label>
                        <div class="field-value">{{ $booking->start_date->format('d.m.Y') }}</div>
                    </div>
                    <div class="field">
                        <label>Выезд</label>
                        <div class="field-value">{{ $booking->end_date->format('d.m.Y') }}</div>
                    </div>
                    <div class="field">
                        <label>Гостей</label>
                        <div class="field-value">{{ $booking->guests }}</div>
                    </div>
                </div>
                <div class="grid-2" style="margin-top:14px;">
                    <div class="field">
                        <label>Ночей</label>
                        <div class="field-value">{{ $nights }}</div>
                    </div>
                    <div class="field">
                        <label>Время заезда</label>
                        <div class="field-value">{{ $booking->arrival_time ?? '14:00' }}</div>
                    </div>
                </div>
            </div>

            {{-- ГОСТЬ --}}
            <div class="card">
                <div class="card-title">Данные гостя</div>
                <div style="display:flex; gap:14px; align-items:center; margin-bottom:16px;">
                    <div class="guest-avatar">
                        {{ mb_substr($booking->first_name,0,1) }}{{ mb_substr($booking->last_name,0,1) }}
                    </div>
                    <div>
                        <div style="font-weight:700; font-size:16px;">{{ $booking->full_name }}</div>
                        <div class="small">{{ $booking->country }}</div>
                        @if($booking->is_business_trip)
                            <span class="badge badge-gray" style="margin-top:6px;">Командировка</span>
                        @endif
                    </div>
                </div>
                <hr class="soft">
                <div class="grid-2">
                    <div class="field">
                        <label>Email</label>
                        <div class="field-value">{{ $booking->email }}</div>
                    </div>
                    <div class="field">
                        <label>Телефон</label>
                        <div class="field-value">{{ $booking->phone }}</div>
                    </div>
                </div>
            </div>

            {{-- НОМЕР --}}
            <div class="card">
                <div class="card-title">Номер</div>
                <div style="font-weight:700; font-size:15px;">{{ $booking->room->title }}</div>
                <div class="small">{{ $booking->hotel->title }}</div>
                <div class="small">{{ $booking->hotel->address }}</div>
                <hr class="soft">
                <div class="grid-2">
                    <div class="field">
                        <label>Вместимость</label>
                        <div class="field-value">{{ $booking->room->capacity }} гостей</div>
                    </div>
                    <div class="field">
                        <label>Кроватей</label>
                        <div class="field-value">{{ $booking->room->beds }}</div>
                    </div>
                    <div class="field">
                        <label>Ванных</label>
                        <div class="field-value">{{ $booking->room->bathrooms }}</div>
                    </div>
                    <div class="field">
                        <label>Цена за ночь</label>
                        <div class="field-value">{{ number_format($booking->room->price, 0, '', ' ') }} ₸</div>
                    </div>
                </div>
            </div>

            {{-- ИСТОЧНИК --}}
            <div class="card">
                <div class="card-title">Дополнительно</div>
                <div class="grid-2">
                    <div class="field">
                        <label>Источник</label>
                        <div class="field-value">{{ $booking->source }}</div>
                    </div>
                    <div class="field">
                        <label>Тип</label>
                        <div class="field-value">{{ $booking->type ?? '—' }}</div>
                    </div>
                </div>
                @if($booking->special_requests)
                    <div class="field" style="margin-top:14px;">
                        <label>Особые пожелания</label>
                        <div class="field-value" style="align-items:flex-start; min-height:80px;">
                            {{ $booking->special_requests }}
                        </div>
                    </div>
                @endif
            </div>

        </div>

        {{-- RIGHT --}}
        <div>
            <div class="card side-card">
                <div class="card-title">Статус и оплата</div>

                <div class="field" style="margin-bottom:14px;">
                    <label>Статус</label>
                    <select id="statusSelect" class="select">
                        <option value="booked"      {{ $booking->status == 'booked'      ? 'selected' : '' }}>Забронировано</option>
                        <option value="checked_in"  {{ $booking->status == 'checked_in'  ? 'selected' : '' }}>Заселено</option>
                        <option value="checked_out" {{ $booking->status == 'checked_out' ? 'selected' : '' }}>Выселено</option>
                        <option value="cancelled"   {{ $booking->status == 'cancelled'   ? 'selected' : '' }}>Отменено</option>
                    </select>
                </div>

                <div class="field" style="margin-bottom:14px;">
                    <label>Оплачено</label>
                    <select id="paidSelect" class="select">
                        <option value="0" {{ !$booking->is_paid ? 'selected' : '' }}>Нет</option>
                        <option value="1" {{ $booking->is_paid  ? 'selected' : '' }}>Да</option>
                    </select>
                </div>

                <hr class="soft">

                <div class="info-line">
                    <span>Проживание</span>
                    <strong>{{ number_format($booking->price_for_period ?? 0, 0, '.', ' ') }} ₸</strong>
                </div>
                <div class="info-line">
                    <span>Налог</span>
                    <strong>{{ number_format($booking->tax ?? 0, 0, '.', ' ') }} ₸</strong>
                </div>
                <div class="info-line" style="font-size:16px;">
                    <span style="font-weight:700;">Итого</span>
                    <strong>{{ number_format($booking->total_price ?? 0, 0, '.', ' ') }} ₸</strong>
                </div>

                <div style="margin-top:22px;">
                    <button id="saveBtn" class="submit-btn">Сохранить</button>
                </div>

                <div style="margin-top:10px;">
                    <a href="{{ route('voyager.bookings.edit', $booking->id) }}"
                       style="display:block; text-align:center; padding:12px; border:1px solid #e5e7eb;
                              border-radius:14px; font-size:14px; font-weight:600; color:#374151;
                              text-decoration:none; transition:.2s;"
                       onmouseover="this.style.background='#f9fafb'"
                       onmouseout="this.style.background='transparent'">
                        Редактировать
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
<script>
document.getElementById('saveBtn').addEventListener('click', async () => {
    const status  = document.getElementById('statusSelect').value;
    const is_paid = document.getElementById('paidSelect').value;

    const res = await fetch("{{ route('bookings.quick-update', $booking->id) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ status, is_paid })
    });

    const data = await res.json();
    if (data.success) location.reload();
});
</script>
@endsection