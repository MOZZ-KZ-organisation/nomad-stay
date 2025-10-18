@php
    use Illuminate\Database\Eloquent\Collection;
    if ($dataTypeContent instanceof Collection) {
        $hotel = $data; // Voyager даёт текущий элемент как $data
    } else {
        $hotel = $dataTypeContent;
    }
    $nearby = $hotel->nearby ?? null;
@endphp
<div>
    @if(!$nearby)
        <p>Нет данных.</p>
        <a href="{{ route('voyager.hotel-nearbies.create', ['hotel_id' => $hotel->id]) }}"
           class="btn btn-sm btn-success">
            Добавить
        </a>
    @else
        <ul>
            <li>Метро: {{ $nearby->metro ?? '—' }}</li>
            <li>Станция: {{ $nearby->station ?? '—' }}</li>
            <li>Парк: {{ $nearby->park ?? '—' }}</li>
            <li>Аэропорт: {{ $nearby->airport ?? '—' }}</li>
        </ul>
        <a href="{{ route('voyager.hotel-nearbies.edit', $nearby->id) }}"
           class="btn btn-sm btn-primary">
            Изменить
        </a>
    @endif
</div>