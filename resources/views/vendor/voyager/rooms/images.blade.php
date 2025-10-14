@if($data->images->count())
    <div style="display:flex; gap:5px; flex-wrap:wrap;">
        @foreach($data->images as $img)
            <img src="{{ $img->path }}" width="100" height="70" style="object-fit:cover; border-radius:8px;">
        @endforeach
    </div>
@else
    <span class="text-muted">Нет фото</span>
@endif
