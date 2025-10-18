@php
    $nearby = $dataTypeContent->nearby ?? null;
@endphp
<div class="panel panel-bordered">
    <div class="panel-heading">
        <h3 class="panel-title">Близлежащие объекты</h3>
    </div>
    <div class="panel-body">
        @if(!$nearby)
            <p>Для этого отеля еще не добавлены данные о близлежащих объектах.</p>
            <a href="{{ route('voyager.hotel-nearby.create', ['hotel_id' => $dataTypeContent->id]) }}" 
               class="btn btn-primary">
                <i class="voyager-plus"></i> Добавить
            </a>
        @else
            <table class="table table-bordered">
                <tr>
                    <th>Метро</th>
                    <td>{{ $nearby->metro ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Станция</th>
                    <td>{{ $nearby->station ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Аэропорт</th>
                    <td>{{ $nearby->airport ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Достопримечательности</th>
                    <td>{{ $nearby->attractions ?? '—' }}</td>
                </tr>
            </table>
            <a href="{{ route('voyager.hotel-nearby.edit', $nearby->id) }}" 
               class="btn btn-warning">
                <i class="voyager-edit"></i> Изменить
            </a>
        @endif
    </div>
</div>