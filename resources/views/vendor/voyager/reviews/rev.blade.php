@php
    $booking = $data ?? $dataTypeContent; 
@endphp

@if($booking->review)
    <div style="display:flex; flex-direction:column; gap:4px;">
        <a href="{{ route('voyager.reviews.show', $booking->review->id) }}" 
           class="text-primary" 
           style="font-weight:500; text-decoration:none;">
            ⭐ {{ $booking->review->rating }}/10 — {{ Str::limit($booking->review->comment, 50) }}
        </a>
        <small class="text-muted" style="text-align: right">
            {{ $booking->review->created_at->format('d.m.Y') }}
        </small>
    </div>
@else
    <span class="text-muted">Нет отзыва</span>
@endif