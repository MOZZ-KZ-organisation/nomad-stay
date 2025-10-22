<td>
    @if($booking->review)
        <a href="{{ route('voyager.reviews.show', $booking->review->id) }}">
            ⭐ {{ $booking->review->rating }} — {{ Str::limit($booking->review->comment, 40) }}
        </a>
    @else
        <span class="text-muted">—</span>
    @endif
</td>