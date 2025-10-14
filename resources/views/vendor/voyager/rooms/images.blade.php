@php
    $images = $data->images ?? [];
    if (is_string($images)) {
        $decoded = json_decode(html_entity_decode($images), true);
        if (!is_array($decoded)) {
            $clean = trim($images, '[]');
            $clean = str_replace(['&quot;', '"'], '', $clean);
            $decoded = array_map('trim', explode(',', $clean));
        }

        $images = $decoded;
    }
    if ($images instanceof \Illuminate\Support\Collection) {
        $images = $images->pluck('path')->toArray();
    }
    $images = array_map(fn($img) => preg_replace('#/{2,}#', '/', trim($img)), $images);
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