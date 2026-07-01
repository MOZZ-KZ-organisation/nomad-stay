<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminReviewController extends Controller
{
    /**
     * GET /admin-api/reviews
     * Список всех отзывов с фильтрами.
     */
    public function index(Request $request)
    {
        $user      = $request->user();
        $isManager = $user->isHotelManager();
        $query = Review::with(['user:id,name,email', 'hotel:id,title', 'images'])->latest();
        if ($isManager) {
            $query->where('hotel_id', $user->managedHotel?->id);
        } elseif ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }
        $reviews = $query->paginate($request->get('per_page', 20));
        return response()->json([
            'data' => $reviews->map(fn($r) => $this->formatReview($r)),
            'meta' => [
                'total'        => $reviews->total(),
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * DELETE /admin-api/reviews/{id}
     * Удалить отзыв (только admin).
     */
    public function destroy(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $review = Review::findOrFail($id);
        $review->images()->each(function ($img) {
            if ($img->path && Storage::disk('public')->exists($img->path)) {
                Storage::disk('public')->delete($img->path);
            }
            $img->delete();
        });
        $review->delete();
        return response()->json(['message' => 'Отзыв удалён']);
    }

    protected function formatReview(Review $r): array
    {
        return [
            'id'      => $r->id,
            'rating'  => $r->rating,
            'comment' => $r->comment,
            'user'    => ['id' => $r->user?->id, 'name' => $r->user?->name, 'email' => $r->user?->email],
            'hotel'   => ['id' => $r->hotel?->id, 'title' => $r->hotel?->title],
            'media'   => $r->images?->map(fn($m) => url(Storage::url($m->path))),
            'created_at' => $r->created_at->format('d.m.Y H:i'),
        ];
    }
}