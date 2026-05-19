@extends('voyager::master')

@section('content')
@php
    $isEdit = isset($dataTypeContent->id);
    $booking = $dataTypeContent;
    $user = auth()->user();

    // Менеджер видит только свои номера
    $rooms = $user->isHotelManager()
        ? \App\Models\Room::with('hotel')->where('hotel_id', $user->managedHotel?->id)->get()
        : \App\Models\Room::with('hotel')->get();

    $statuses = [
        'booked'      => 'Забронировано',
        'checked_in'  => 'Заселено',
        'checked_out' => 'Выселено',
        'cancelled'   => 'Отменено',
    ];
@endphp

<style>
.page-wrap{
    max-width:1100px;
    margin:24px auto;
}

.page-header{
    display:flex;
    align-items:center;
    gap:14px;
    margin:10px;
}

.back-btn{
    width:42px;
    height:42px;
    border-radius:12px;
    border:1px solid #e5e7eb;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#111;
    text-decoration:none;
    transition:.2s;
}

.back-btn:hover{
    background:#f8fafc;
    transform:translateY(-1px);
}

.page-title{
    font-size:28px;
    font-weight:700;
    color:#111827;
    padding-left: 1rem;
}

.form-grid{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:18px;
}

.card{
    background:#fff;
    border-radius:18px;
    padding:22px;
    box-shadow:0 10px 30px rgba(0,0,0,.05);
    margin-bottom:18px;
}

.card-title{
    font-size:15px;
    font-weight:700;
    margin-bottom:18px;
    color:#111827;
}

.grid-4{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
}

.grid-3{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:14px;
}

.grid-2{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:14px;
}

.field{
    display:flex;
    flex-direction:column;
}

.field label{
    font-size:13px;
    font-weight:600;
    margin-bottom:7px;
    color:#374151;
}

.input,
.select,
.textarea{
    width:100%;
    border:1px solid #e5e7eb;
    border-radius:12px;
    background:#fff;
    padding:12px 14px;
    font-size:14px;
    transition:.2s;
}

.input:focus,
.select:focus,
.textarea:focus{
    outline:none;
    border-color:#6366f1;
    box-shadow:0 0 0 4px rgba(99,102,241,.08);
}

.textarea{
    min-height:110px;
    resize:vertical;
}

.checkbox-wrap{
    display:flex;
    align-items:center;
    gap:10px;
    margin-top:16px;
}

.checkbox-wrap input{
    width:18px;
    height:18px;
}

.submit-btn{
    width:100%;
    height:52px;
    border:none;
    border-radius:14px;
    background:#111827;
    color:#fff;
    font-size:15px;
    font-weight:600;
    transition:.2s;
}

.submit-btn:hover{
    background:#000;
}

.side-card{
    position:sticky;
    top:20px;
}

.info-line{
    display:flex;
    justify-content:space-between;
    padding:10px 0;
    border-bottom:1px solid #f1f5f9;
    font-size:14px;
}

.info-line:last-child{
    border-bottom:none;
}

.icon{
    width:16px;
    height:16px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    color:#6b7280;
}

.field-icon{
    position:relative;
}

.field-icon svg{
    position:absolute;
    left:12px;
    top:50%;
    transform:translateY(-50%);
    width:16px;
    height:16px;
    color:#9ca3af;
}

.field-icon input{
    padding-left:42px;
}

@media(max-width:900px){
    .form-grid{
        grid-template-columns:1fr;
    }

    .grid-4,
    .grid-3,
    .grid-2{
        grid-template-columns:1fr;
    }
}
</style>

<div class="page-wrap">

    <div class="page-header">

        <a href="{{ route('voyager.bookings.index') }}" class="back-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2"/>
            </svg>
        </a>

        <div class="page-title">
            {{ $isEdit ? 'Редактирование брони' : 'Новая бронь' }}
        </div>

    </div>

    <form
        action="{{ $isEdit
            ? route('voyager.bookings.update', $booking->id)
            : route('voyager.bookings.store') }}"
        method="POST"
    >
        @csrf

        @if($isEdit)
            @method('PUT')
        @endif

        <div class="form-grid">

            {{-- LEFT --}}
            <div>

                {{-- ROOM + DATES --}}
                <div class="card">

                    <div class="card-title">
                        Номер и даты
                    </div>

                    <div class="grid-4">

                        <input
                            type="hidden"
                            name="hotel_id"
                            id="hotel_id"
                            value="{{ old('hotel_id', $booking->hotel_id ?? '') }}"
                        >
                        <div class="field">
                            <label>Номер</label>

                            <select
                                name="room_id"
                                id="room_select"
                                class="select"
                                required
                            >
                                <option value="">
                                    Выберите номер
                                </option>

                                @foreach($rooms as $room)
                                    <option
                                        value="{{ $room->id }}"
                                        data-hotel="{{ $room->hotel_id }}"
                                        {{
                                            old(
                                                'room_id',
                                                request('room_id', $booking->room_id ?? '')
                                            ) == $room->id
                                            ? 'selected'
                                            : ''
                                        }}
                                    >
                                        {{ $room->title }} — {{ $room->hotel->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label>Заезд</label>

                            <input
                                type="date"
                                name="start_date"
                                class="input"
                                required
                                value="{{ old('start_date', request('start_date', optional($booking->start_date)->format('Y-m-d'))) }}"
                            >
                        </div>

                        <div class="field">
                            <label>Выезд</label>

                            <input
                                type="date"
                                name="end_date"
                                class="input"
                                required
                                value="{{ old(
                                    'end_date',
                                    isset($booking->end_date)
                                        ? \Carbon\Carbon::parse($booking->end_date)->format('Y-m-d')
                                        : (
                                            request('start_date')
                                                ? \Carbon\Carbon::parse(request('start_date'))->addDay()->format('Y-m-d')
                                                : ''
                                        )
                                ) }}"
                            >
                        </div>

                        <div class="field">
                            <label>Гостей</label>

                            <input
                                type="number"
                                min="1"
                                name="guests"
                                class="input"
                                required
                                value="{{ old('guests', $booking->guests ?? 1) }}"
                            >
                        </div>

                    </div>

                </div>

                {{-- GUEST --}}
                <div class="card">

                    <div class="card-title">
                        Данные гостя
                    </div>

                    <div class="grid-3">

                        <div class="field">
                            <label>Имя</label>

                            <input
                                type="text"
                                name="first_name"
                                class="input"
                                required
                                value="{{ old('first_name', $booking->first_name) }}"
                            >
                        </div>

                        <div class="field">
                            <label>Фамилия</label>

                            <input
                                type="text"
                                name="last_name"
                                class="input"
                                required
                                value="{{ old('last_name', $booking->last_name) }}"
                            >
                        </div>

                        <div class="field">
                            <label>Страна</label>

                            <input
                                type="text"
                                name="country"
                                class="input"
                                required
                                value="{{ old('country', $booking->country) }}"
                            >
                        </div>

                    </div>

                    <div class="grid-2" style="margin-top:14px;">

                        <div class="field">
                            <label>Email</label>

                            <input
                                type="email"
                                name="email"
                                class="input"
                                required
                                value="{{ old('email', $booking->email) }}"
                            >
                        </div>

                        <div class="field">
                            <label>Телефон</label>

                            <input
                                type="text"
                                name="phone"
                                class="input"
                                required
                                value="{{ old('phone', $booking->phone) }}"
                            >
                        </div>

                    </div>

                </div>

                {{-- EXTRA --}}
                <div class="card">

                    <div class="card-title">
                        Дополнительно
                    </div>

                    <div class="grid-2">

                        <div class="field">
                            <label>Время заезда</label>

                            <input
                                type="time"
                                name="arrival_time"
                                class="input"
                                value="{{ old('arrival_time', $booking->arrival_time?->format('H:i')) }}"
                            >
                        </div>

                        <div class="field">
                            <label>Источник</label>

                            <input
                                type="text"
                                name="source"
                                class="input"
                                value="{{ old('source', $booking->source ?? 'admin') }}"
                            >
                        </div>

                    </div>

                    <div class="field" style="margin-top:14px;">
                        <label>Особые пожелания</label>

                        <textarea
                            name="special_requests"
                            class="textarea"
                        >{{ old('special_requests', $booking->special_requests) }}</textarea>
                    </div>

                    <div class="field">
                        <label>Командировка</label>

                        <select
                            name="is_business_trip"
                            class="select"
                        >
                            <option
                                value="0"
                                {{ old('is_business_trip', $booking->is_business_trip ?? 0) == 0 ? 'selected' : '' }}
                            >
                                Нет
                            </option>

                            <option
                                value="1"
                                {{ old('is_business_trip', $booking->is_business_trip ?? 0) == 1 ? 'selected' : '' }}
                            >
                                Да
                            </option>
                        </select>
                    </div>

                </div>

            </div>

            {{-- RIGHT --}}
            <div>

                <div class="card side-card">

                    <div class="card-title">
                        Статус и оплата
                    </div>

                    <div class="field">
                        <label>Статус</label>

                        <select
                            name="status"
                            class="select"
                        >
                            @foreach($statuses as $key => $label)
                                <option
                                    value="{{ $key }}"
                                    {{
                                        old('status', $booking->status ?? 'booked')
                                        == $key
                                        ? 'selected'
                                        : ''
                                    }}
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label>Оплачено</label>

                        <select
                            name="is_paid"
                            class="select"
                        >
                            <option
                                value="0"
                                {{ old('is_paid', $booking->is_paid ?? 0) == 0 ? 'selected' : '' }}
                            >
                                Нет
                            </option>

                            <option
                                value="1"
                                {{ old('is_paid', $booking->is_paid ?? 0) == 1 ? 'selected' : '' }}
                            >
                                Да
                            </option>
                        </select>
                    </div>

                    <div style="height:18px;"></div>

                    @if($isEdit)

                        <div class="info-line">
                            <span>Стоимость</span>
                            <strong>
                                {{ number_format($booking->price_for_period ?? 0, 0, '.', ' ') }} ₸
                            </strong>
                        </div>

                        <div class="info-line">
                            <span>Налог</span>
                            <strong>
                                {{ number_format($booking->tax ?? 0, 0, '.', ' ') }} ₸
                            </strong>
                        </div>

                        <div class="info-line">
                            <span>Итого</span>
                            <strong>
                                {{ number_format($booking->total_price ?? 0, 0, '.', ' ') }} ₸
                            </strong>
                        </div>

                    @endif

                    <div style="margin-top:22px;">

                        <button
                            type="submit"
                            class="submit-btn"
                        >
                            {{ $isEdit ? 'Сохранить изменения' : 'Создать бронь' }}
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const roomSelect = document.getElementById('room_select');
    const hotelInput = document.getElementById('hotel_id');

    function syncHotel() {
        const option = roomSelect.options[roomSelect.selectedIndex];

        hotelInput.value = option.dataset.hotel || '';
    }

    syncHotel();

    roomSelect.addEventListener('change', syncHotel);
});
</script>
@endsection