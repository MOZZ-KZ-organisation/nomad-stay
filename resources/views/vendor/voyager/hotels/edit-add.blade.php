@php
    $edit = !is_null($dataTypeContent->getKey());
    $add  = is_null($dataTypeContent->getKey());
    $hotel = $dataTypeContent;
    $isAdmin = auth()->user()->isAdmin();
    $isManager = auth()->user()->isHotelManager();

    // Хелпер: безопасно получить editRow
    $getRow = fn(string $field) => $dataType->editRows->firstWhere('field', $field);
    $renderField = function(string $field) use ($dataType, $dataTypeContent, $getRow) {
        $row = $getRow($field);
        if (!$row) return '';
        return app('voyager')->formField($row, $dataType, $dataTypeContent);
    };
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .hotel-edit-wrap {
            padding: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .hotel-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
            align-items: start;
        }
        .card-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-section-header {
            padding: 14px 20px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-section-body {
            padding: 20px;
        }
        .field-group {
            margin-bottom: 18px;
        }
        .field-group:last-child {
            margin-bottom: 0;
        }
        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .field-group .form-control {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 10px 14px;
            font-size: 14px;
            transition: border-color 0.2s;
            box-shadow: none;
        }
        .field-group .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
            outline: none;
        }
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .stars-select {
            display: flex;
            gap: 8px;
        }
        .stars-select label {
            cursor: pointer;
            font-size: 22px;
            color: #d1d5db;
            transition: color 0.15s;
            user-select: none;
        }
        .stars-select input[type=radio] { display: none; }
        .stars-select input[type=radio]:checked ~ label,
        .stars-select label:hover,
        .stars-select label:hover ~ label { color: #facc15; }
        .status-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 0;
        }
        .status-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #d1d5db;
            transition: background 0.2s;
        }
        .status-dot.active { background: #22c55e; }
        .save-bar {
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px solid #e5e7eb;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            z-index: 100;
            box-shadow: 0 -4px 16px rgba(0,0,0,0.06);
        }
        .btn-save {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-save:hover { background: #1d4ed8; }
        .btn-cancel {
            background: transparent;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-cancel:hover { background: #f9fafb; color: #374151; }
        .nearby-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .nearby-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: #f9fafb;
            border-radius: 8px;
            font-size: 13px;
        }
        .nearby-icon { font-size: 18px; }
        .nearby-label { color: #9ca3af; font-size: 11px; }
        .nearby-value { font-weight: 600; color: #111; }
        .room-list { display: flex; flex-direction: column; gap: 8px; }
        .room-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: #f9fafb;
            border-radius: 8px;
            text-decoration: none;
            color: #111;
            font-size: 13px;
            transition: background 0.15s;
        }
        .room-item:hover { background: #eff6ff; color: #2563eb; }
        .room-item-meta { color: #9ca3af; font-size: 12px; }
        .amenity-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .amenity-chip {
            padding: 4px 12px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #dc2626;
        }
        /* Переопределяем стандартные Voyager стили для select */
        .field-group select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%236b7280' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }
    </style>
@stop

@section('page_title', ($edit ? 'Редактировать' : 'Добавить').' отель')

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ $edit ? 'Редактировать отель' : 'Добавить отель' }}
        @if($edit)
            <small style="font-size:14px; color:#9ca3af; font-weight:400;">
                — {{ $hotel->title }}
            </small>
        @endif
    </h1>
@stop

@section('content')
<div class="hotel-edit-wrap">

    @if (count($errors) > 0)
        <div class="error-box">
            @foreach ($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form
        action="{{ $edit ? route('voyager.'.$dataType->slug.'.update', $hotel->getKey()) : route('voyager.'.$dataType->slug.'.store') }}"
        method="POST"
        enctype="multipart/form-data"
        id="hotelForm"
    >
        @if($edit) {{ method_field('PUT') }} @endif
        {{ csrf_field() }}

        <div class="hotel-grid">

            {{-- ЛЕВАЯ КОЛОНКА --}}
            <div>
                @if($isManager)
                {{-- Основная информация --}}
                <div class="card-section">
                    <div class="card-section-header">Основная информация</div>
                    <div class="card-section-body">

                        <div class="field-group">
                            <label class="field-label">Название отеля *</label>
                            @php $row = $dataType->editRows->firstWhere('field', 'title') @endphp
                            {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                        </div>

                        <div class="field-group">
                            <label class="field-label">Город</label>
                            @php $row = $dataType->editRows->firstWhere('field', 'hotel_hasone_city_relationship') @endphp
                            @if($row)
                                @include('voyager::formfields.relationship', ['options' => $row->details, 'row' => $row])
                            @endif
                        </div>

                        <div class="field-group">
                            <label class="field-label">Адрес</label>
                            @php $row = $dataType->editRows->firstWhere('field', 'address') @endphp
                            {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                        </div>

                        <div class="field-group">
                            <label class="field-label">Описание</label>
                            @php $row = $dataType->editRows->firstWhere('field', 'description') @endphp
                            {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                        </div>

                    </div>
                </div>

                {{-- Характеристики --}}
                <div class="card-section">
                    <div class="card-section-header">Характеристики</div>
                    <div class="card-section-body">

                        <div class="two-col">
                            <div class="field-group">
                                <label class="field-label">Тип</label>
                                @php $row = $dataType->editRows->firstWhere('field', 'type') @endphp
                                @if($row) {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!} @endif
                            </div>
                            <div class="field-group">
                                <label class="field-label">Мин. цена (₸)</label>
                                @php $row = $dataType->editRows->firstWhere('field', 'min_price') @endphp
                                @if($row)
                                    {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                @else
                                    <p class="form-control-static" style="padding-top:7px; color:#555;">
                                        {{ $hotel->min_price ? number_format($hotel->min_price, 0, '.', ' ').' ₸' : '—' }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="two-col">
                            <div class="field-group">
                                <label class="field-label">Цена отмены брони (₸)</label>
                                @php $row = $dataType->editRows->firstWhere('field', 'cancellation_fee') @endphp
                                @if($row) {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!} @endif
                            </div>
                            <div class="field-group">
                                <label class="field-label">Звёзды</label>
                                @php $row = $dataType->editRows->firstWhere('field', 'stars') @endphp
                                @if($row) {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!} @endif
                            </div>
                        </div>

                        <div class="two-col">
                            <div class="field-group">
                                <label class="field-label">Широта</label>
                                @php $row = $dataType->editRows->firstWhere('field', 'latitude') @endphp
                                @if($row) {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!} @endif
                            </div>
                            <div class="field-group">
                                <label class="field-label">Долгота</label>
                                @php $row = $dataType->editRows->firstWhere('field', 'longitude') @endphp
                                @if($row) {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!} @endif
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Удобства --}}
                <div class="card-section">
                    <div class="card-section-header">Удобства</div>
                    <div class="card-section-body">
                        @php $row = $dataType->editRows->firstWhere('field', 'hotel_hasone_amenity_relationship') @endphp
                        @if($row)
                            @include('voyager::formfields.relationship', ['options' => $row->details, 'row' => $row])
                        @endif
                    </div>
                </div>

                {{-- Близлежащие места --}}
                <div class="card-section">
                    <div class="card-section-header">Близлежащие места</div>
                    <div class="card-section-body">
                        @php $row = $dataType->editRows->firstWhere('field', 'nearby_relationship') @endphp
                        @if($row && isset($row->details->view))
                            @include($row->details->view, [
                                'row' => $row,
                                'dataType' => $dataType,
                                'dataTypeContent' => $dataTypeContent,
                                'content' => $dataTypeContent->{$row->field},
                                'view' => 'edit',
                                'options' => $row->details
                            ])
                        @endif
                    </div>
                </div>
                    @else
                    {{-- Админ видит данные только для чтения --}}
                    <div class="card-section">
                        <div class="card-section-header">Информация об отеле</div>
                        <div class="card-section-body">
                            <div class="field-group">
                                <label class="field-label">Название</label>
                                <p style="font-size:15px; font-weight:600; color:#111; margin:0;">{{ $hotel->title }}</p>
                                <input type="hidden" name="title" value="{{ $hotel->title }}">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Адрес</label>
                                <p style="font-size:14px; color:#374151; margin:0;">{{ $hotel->address ?: '—' }}</p>
                                <input type="hidden" name="address" value="{{ $hotel->address }}">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Тип</label>
                                <p style="font-size:14px; color:#374151; margin:0;">{{ $hotel->type ?: '—' }}</p>
                                <input type="hidden" name="type" value="{{ $hotel->type }}">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Звёзды</label>
                                <p style="font-size:14px; color:#374151; margin:0;">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span style="color:{{ $i <= $hotel->stars ? '#facc15' : '#d1d5db' }}; font-size:18px;">★</span>
                                    @endfor
                                </p>
                                <input type="hidden" name="stars" value="{{ $hotel->stars }}">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Мин. цена</label>
                                <p style="font-size:14px; color:#374151; margin:0;">
                                    {{ $hotel->min_price ? number_format($hotel->min_price, 0, '.', ' ').' ₸' : '—' }}
                                </p>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Описание</label>
                                <p style="font-size:14px; color:#374151; margin:0; line-height:1.6;">
                                    {{ $hotel->description ?: '—' }}
                                </p>
                                <input type="hidden" name="description" value="{{ $hotel->description }}">
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ПРАВАЯ КОЛОНКА --}}
            <div>

                {{-- Статус --}}
                <div class="card-section">
                    <div class="card-section-header">Статус</div>
                    <div class="card-section-body">
                        @php
                            $row = $dataType->editRows->firstWhere('field', 'is_active');
                            $isActive = (bool) $hotel->is_active;
                        @endphp
                        <div class="status-toggle">
                            <div class="status-dot {{ $isActive ? 'active' : '' }}" id="statusDot"></div>
                            <span id="statusText" style="font-size:13px; font-weight:600;">
                                {{ $isActive ? 'Активен' : 'Неактивен' }}
                            </span>
                        </div>

                        @if($isAdmin && $row)
                            {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                        @else
                            <input type="hidden" name="is_active" value="{{ $hotel->is_active ? 1 : 0 }}">
                            <p style="font-size:13px; color:#6b7280; margin:0;">
                                {{ $isActive ? 'Отель активен и виден пользователям' : 'Ожидает активации администратором' }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Фото --}}
                <div class="card-section">
                    <div class="card-section-header">Фото</div>
                    <div class="card-section-body">
                        @php $row = $dataType->editRows->firstWhere('field', 'hotel_hasone_hotel_image_relationship') @endphp
                        @if($row && isset($row->details->view))
                            @include($row->details->view, [
                                'row' => $row,
                                'dataType' => $dataType,
                                'dataTypeContent' => $dataTypeContent,
                                'content' => $dataTypeContent->{$row->field},
                                'view' => 'edit',
                                'options' => $row->details
                            ])
                        @endif
                    </div>
                </div>

                {{-- Номера --}}
                @if($edit)
                <div class="card-section">
                    <div class="card-section-header">Номера</div>
                    <div class="card-section-body">
                        @php $row = $dataType->editRows->firstWhere('field', 'hotel_hasone_room_relationship') @endphp
                        @if($row && isset($row->details->view))
                            @include($row->details->view, [
                                'row' => $row,
                                'dataType' => $dataType,
                                'dataTypeContent' => $dataTypeContent,
                                'content' => $dataTypeContent->{$row->field},
                                'view' => 'edit',
                                'options' => $row->details
                            ])
                        @endif
                    </div>
                </div>
                @endif

            </div>
        </div>

        {{-- Скрытые поля --}}
        @php
            $hiddenFields = ['id', 'slug', 'city_id'];
        @endphp
        @foreach($dataType->editRows as $row)
            @if(in_array($row->field, $hiddenFields))
                <input type="hidden" name="{{ $row->field }}" value="{{ $dataTypeContent->{$row->field} }}">
            @endif
        @endforeach

        {{-- Sticky save bar --}}
        <div class="save-bar">
            <a href="{{ route('voyager.'.$dataType->slug.'.index') }}" class="btn-cancel">
                ← Назад
            </a>
            <div style="display:flex; gap:10px; align-items:center;">
                @if($edit)
                    <span style="font-size:13px; color:#9ca3af;">
                        Последнее обновление: {{ $hotel->updated_at?->format('d.m.Y H:i') }}
                    </span>
                @endif
                <button type="submit" class="btn-save">
                    Сохранить
                </button>
            </div>
        </div>

    </form>
</div>

{{-- Modal удаления файлов (стандартный Voyager) --}}
<div class="modal fade modal-danger" id="confirm_delete_modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Подтвердите удаление</h4>
            </div>
            <div class="modal-body">
                <h4>Удалить '<span class="confirm_delete_name"></span>'?</h4>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirm_delete">Удалить</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('javascript')
<script>
var params = {};
var $file;

function deleteHandler(tag, isMulti) {
    return function() {
        $file = $(this).siblings(tag);
        params = {
            slug: '{{ $dataType->slug }}',
            filename: $file.data('file-name'),
            id: $file.data('id'),
            field: $file.parent().data('field-name'),
            multi: isMulti,
            _token: '{{ csrf_token() }}'
        };
        $('.confirm_delete_name').text(params.filename);
        $('#confirm_delete_modal').modal('show');
    };
}

$(document).ready(function() {
    $('.toggleswitch').bootstrapToggle();

    // Синхронизация статуса
    $('input[name="is_active"]').on('change', function() {
        const active = $(this).is(':checked');
        $('#statusDot').toggleClass('active', active);
        $('#statusText').text(active ? 'Активен' : 'Неактивен');
    });

    // Datepicker
    $('.form-group input[type=date]').each(function(idx, elt) {
        if (elt.hasAttribute('data-datepicker')) {
            elt.type = 'text';
            $(elt).datetimepicker($(elt).data('datepicker'));
        } else if (elt.type != 'date') {
            elt.type = 'text';
            $(elt).datetimepicker({ format: 'L', extraFormats: ['YYYY-MM-DD'] });
        }
    });

    $('.form-group').on('click', '.remove-multi-image', deleteHandler('img', true));
    $('.form-group').on('click', '.remove-single-image', deleteHandler('img', false));
    $('.form-group').on('click', '.remove-multi-file', deleteHandler('a', true));
    $('.form-group').on('click', '.remove-single-file', deleteHandler('a', false));

    $('#confirm_delete').on('click', function() {
        $.post('{{ route('voyager.'.$dataType->slug.'.media.remove') }}', params, function(response) {
            if (response && response.data && response.data.status == 200) {
                toastr.success(response.data.message);
                $file.parent().fadeOut(300, function() { $(this).remove(); });
            } else {
                toastr.error('Ошибка удаления файла');
            }
        });
        $('#confirm_delete_modal').modal('hide');
    });

    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@stop