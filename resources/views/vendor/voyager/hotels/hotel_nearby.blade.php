@php
    use Illuminate\Database\Eloquent\Collection;

    // Если это коллекция (browse), просто показываем кратко
    if ($dataTypeContent instanceof Collection) {
        // browse mode
        $hotel = $data; // Voyager даёт текущий элемент как $data
        $nearby = $hotel->nearby ?? null;
    } else {
        // show mode
        $hotel = $dataTypeContent;
        $nearby = $hotel->nearby ?? null;
    }
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