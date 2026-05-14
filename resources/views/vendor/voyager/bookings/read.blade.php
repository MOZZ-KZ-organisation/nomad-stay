@extends('voyager::master')

@section('content')
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

<div class="container-fluid" style="padding:20px;">

    {{-- HEADER --}}
    <div class="panel panel-bordered">
        <div class="panel-body">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h2 style="margin:0;">Бронь #{{ $booking->id }}</h2>
                    <small>{{ $booking->source }} / {{ $booking->type }}</small>
                </div>

                <div>
                    <span class="label {{ $status['class'] }}">
                        {{ $status['label'] }}
                    </span>

                    <span class="label" style="background:{{ $booking->is_paid ? '#dfffe0' : '#ffe0e0' }};color:#000;">
                        {{ $booking->is_paid ? 'Оплачено' : 'Не оплачено' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- INFO GRID --}}
    <div class="row">

        {{-- LEFT --}}
        <div class="col-md-8">

            {{-- DATES --}}
            <div class="panel panel-bordered">
                <div class="panel-heading">Даты</div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3">
                            <b>Заезд</b><br>
                            {{ $booking->start_date->format('d.m.Y') }}<br>
                            <small>{{ $booking->arrival_time }}</small>
                        </div>

                        <div class="col-md-3">
                            <b>Выезд</b><br>
                            {{ $booking->end_date->format('d.m.Y') }}
                        </div>

                        <div class="col-md-3">
                            <b>Ночей</b><br>
                            {{ $nights }}
                        </div>

                        <div class="col-md-3">
                            <b>Гостей</b><br>
                            {{ $booking->guests }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- GUEST --}}
            <div class="panel panel-bordered">
                <div class="panel-heading">Гость</div>
                <div class="panel-body">
                    <h4>{{ $booking->full_name }}</h4>
                    <p>{{ $booking->phone }}</p>
                    <p>{{ $booking->email }}</p>
                    <p>{{ $booking->country }}</p>

                    @if($booking->is_business_trip)
                        <span class="label label-info">Командировка</span>
                    @endif
                </div>
            </div>

            {{-- ROOM --}}
            <div class="panel panel-bordered">
                <div class="panel-heading">Номер</div>
                <div class="panel-body">
                    <h4>{{ $booking->room->title }}</h4>
                    <p>{{ $booking->hotel->title }}</p>
                    <p>{{ $booking->hotel->address }}</p>

                    <hr>

                    <p>Вместимость: {{ $booking->room->capacity }}</p>
                    <p>Кровати: {{ $booking->room->beds }}</p>
                    <p>Ванные: {{ $booking->room->bathrooms }}</p>
                    <p>Цена: {{ formatPrice($booking->room->price) }}</p>
                </div>
            </div>

            {{-- REQUESTS --}}
            @if($booking->special_requests)
            <div class="panel panel-bordered">
                <div class="panel-heading">Особые пожелания</div>
                <div class="panel-body">
                    {{ $booking->special_requests }}
                </div>
            </div>
            @endif

        </div>

        {{-- RIGHT --}}
        <div class="col-md-4">

            {{-- PAYMENT --}}
            <div class="panel panel-bordered">
                <div class="panel-heading">Оплата</div>
                <div class="panel-body">
                    <p>Проживание: {{ formatPrice($booking->price_for_period) }}</p>
                    <p>Налог: {{ formatPrice($booking->tax) }}</p>

                    <hr>

                    <h3>Итого: {{ formatPrice($booking->total_price) }}</h3>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection