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
        <p>Нет данных</p>
        <a href="{{ route('voyager.hotel-nearbies.create', ['hotel_id' => $hotel->id]) }}"
           class="btn btn-sm btn-success">
            Добавить
        </a>
    @else
        @php
            $fields = [
                'Метро' => $nearby->metro,
                'Станция' => $nearby->station,
                'Парк' => $nearby->park,
                'Аэропорт' => $nearby->airport,
            ];
            $filled = array_filter($fields, fn($value) => !empty($value));
        @endphp
        @if(count($filled))
            <div style="margin-bottom:10px;">
                @foreach($filled as $label => $value)
                    <div><strong>{{ $label }}:</strong> {{ $value }}</div>
                @endforeach
            </div>
        @else
            <p>Нет данных</p>
        @endif
        <a href="{{ route('voyager.hotel-nearbies.edit', $nearby->id) }}"
           class="btn btn-sm btn-primary">
            Изменить
        </a>
    @endif
</div>