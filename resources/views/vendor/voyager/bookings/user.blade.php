@php
    $isBrowse = isset($view) && $view === 'browse';
    $isRead   = isset($view) && $view === 'read';
    $isEdit   = isset($dataTypeContent->id);
    $relationshipValue = $dataTypeContent->{$row->field} ?? null;
@endphp
@if($isBrowse || $isRead)
    <span>{{ $relationshipValue ? $relationshipValue->name : '' }}</span>
@elseif($isEdit)
    <input type="text" class="form-control" value="{{ $relationshipValue ? $relationshipValue->name : '' }}" readonly>
    <input type="hidden" name="{{ $row->field }}" value="{{ $relationshipValue ? $relationshipValue->id : '' }}">
@else
    @include('voyager::formfields.relationship')
@endif