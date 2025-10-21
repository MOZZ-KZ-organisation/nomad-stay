<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $hasCompletedBooking = $user->bookings()
            ->where('hotel_id', $data['hotel_id'])
            ->where('status', 'completed') 
            ->exists();
        if (!$hasCompletedBooking) {
            return response()->json(['message' => 'Вы можете оставить отзыв только после завершённого бронирования.'], 403);
        }
        $data['user_id'] = $user->id;
        $review = Review::create($data);
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $file->store('reviews', 'public');
                $review->media()->create(['path' => $path]);
            }
        }
        $mediaUrls = $review->media()->get()->map(fn($m) => url(Storage::url($m->path)))->toArray();
        return response()->json([
			'data' => [
				'id' => $review->id,
				'user' => ['id' => $user->id, 'name' => $user->name],
				'rating' => $review->rating,
				'comment' => $review->comment,
				'created_at' => $review->created_at->format('d.m.Y H:i'),
				'media' => $mediaUrls,
			]
		], 201);
    }
}
