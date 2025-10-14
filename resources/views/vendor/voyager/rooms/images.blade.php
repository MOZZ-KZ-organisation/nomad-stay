@php
    $images = $data->images ?? [];
    if (is_string($images)) {
        $decoded = json_decode($images, true);
        $images = is_array($decoded) ? $decoded : [];
    }
    if ($images instanceof \Illuminate\Support\Collection) {
        $images = $images->pluck('path')->toArray();
    }
@endphp
@if(!empty($images))
    <div style="display:flex; gap:5px; flex-wrap:wrap;">
        @foreach($images as $img)
            <img src="{{ Voyager::image($img) }}" width="100" height="70" style="object-fit:cover; border-radius:8px;">
        @endforeach
    </div>
@else
    <span class="text-muted">Нет фото</span>
@endif