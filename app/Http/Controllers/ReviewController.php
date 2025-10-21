<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;

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
        return new ReviewResource($review);
    }
}
