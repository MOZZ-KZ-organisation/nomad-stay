@php
    // Определяем: это коллекция или одна запись
    $items = $dataTypeContent instanceof \Illuminate\Database\Eloquent\Collection
        ? $dataTypeContent
        : collect([$dataTypeContent]);
@endphp

@foreach ($items as $hotel)
    @php
        $nearby = $hotel->nearby ?? null;
    @endphp

    <div class="panel panel-bordered">
        <div class="panel-heading">
            <h3 class="panel-title">
                {{ $hotel->title ?? 'Отель' }} — Близлежащие объекты
            </h3>
        </div>

        <div class="panel-body">
            @if(!$nearby)
                <p>Нет данных о близлежащих объектах</p>
            @else
                <ul>
                    <li>Метро: {{ $nearby->metro ?? '—' }}</li>
                    <li>Станция: {{ $nearby->station ?? '—' }}</li>
                    <li>Парк: {{ $nearby->park ?? '—' }}</li>
                    <li>Аэропорт: {{ $nearby->airport ?? '—' }}</li>
                </ul>
            @endif
        </div>
    </div>
@endforeach