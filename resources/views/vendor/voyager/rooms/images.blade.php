@php
    $item = $data ?? $dataTypeContent;
    $isEditOrAdd = in_array(Route::currentRouteName(), [
        'voyager.hotels.edit', 'voyager.hotels.create',
        'voyager.rooms.edit', 'voyager.rooms.create'
    ]);
    $prefix = Str::contains(Route::currentRouteName(), 'hotels') ? 'hotel' : 'room';
@endphp
@if(!empty($item->images) && $item->images->count())
    <div id="existing-images" style="display:flex; gap:5px; flex-wrap:wrap; align-items:flex-start;">
        @foreach($item->images as $img)
            <div style="position:relative;" data-image-id="{{ $img->id }}">
                <img src="{{ Voyager::image($img->path) }}" width="100" height="70" style="object-fit:cover; border-radius:8px;">
                @if($isEditOrAdd)
                    <button type="button"
                            onclick="markForDeletion({{ $img->id }}, this)"
                            style="position:absolute; top:-6px; right:-6px; background:#f00; color:#fff; border:none; border-radius:50%; width:18px; height:18px; cursor:pointer;">×
                    </button>
                @endif
            </div>
        @endforeach
    </div>
@else
    <span class="text-muted">Нет фото</span>
@endif
@if($isEditOrAdd)
    <div style="margin-top:10px;">
        <label for="{{ $prefix }}_images_upload">Добавить фото:</label>
        <input type="file" id="{{ $prefix }}_images_upload" name="{{ $prefix }}_images[]" multiple accept="image/*">
    </div>
    <input type="hidden" id="deleted_images" name="deleted_images" value="[]">
    <script>
        function markForDeletion(id, el) {
            el.closest('div[data-image-id]').remove();
            let field = document.getElementById('deleted_images');
            let deleted = JSON.parse(field.value);
            deleted.push(id);
            field.value = JSON.stringify(deleted);
        }
    </script>
@endif