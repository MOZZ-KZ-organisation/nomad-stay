<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminGuestController extends Controller
{
    /**
     * GET /admin-api/guests
     * Список гостей (профили пользователей).
     */
    public function index(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $query = User::withCount(['bookings', 'reviews'])
            ->whereHas('role', fn($q) => $q->where('name', 'user'))
            ->orWhereNull('role_id')
            ->latest();
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
            );
        }
        $users = $query->paginate($request->get('per_page', 20));
        return response()->json([
            'data' => $users->map(fn($u) => $this->formatGuest($u)),
            'meta' => [
                'total'        => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    /**
     * GET /admin-api/guests/{id}
     * Профиль гостя + история бронирований.
     */
    public function show(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $user = User::withCount(['bookings', 'reviews'])->findOrFail($id);
        $bookings = Booking::where('user_id', $id)
            ->with('hotel:id,title')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($b) => [
                'id'             => $b->id,
                'booking_number' => $b->booking_number,
                'hotel'          => $b->hotel?->title,
                'start_date'     => $b->start_date?->format('d.m.Y'),
                'end_date'       => $b->end_date?->format('d.m.Y'),
                'total_price'    => $b->total_price,
                'status'         => $b->status,
                'is_paid'        => $b->is_paid,
            ]);
        $reviews = Review::where('user_id', $id)
            ->with('hotel:id,title')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id'         => $r->id,
                'hotel'      => $r->hotel?->title,
                'rating'     => $r->rating,
                'comment'    => $r->comment,
                'created_at' => $r->created_at->format('d.m.Y'),
            ]);
        return response()->json([
            'data' => array_merge($this->formatGuest($user), [
                'bookings' => $bookings,
                'reviews'  => $reviews,
            ]),
        ]);
    }

    /**
     * PATCH /admin-api/guests/{id}/block
     * Заблокировать/разблокировать гостя (soft delete / restore).
     */
    public function toggleBlock(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $user = User::withTrashed()->findOrFail($id);
        if ($user->trashed()) {
            $user->restore();
            $message = 'Пользователь разблокирован';
        } else {
            $user->delete();
            $message = 'Пользователь заблокирован';
        }
        return response()->json(['message' => $message, 'is_blocked' => $user->trashed()]);
    }

    protected function formatGuest(User $u): array
    {
        return [
            'id'             => $u->id,
            'name'           => $u->name,
            'email'          => $u->email,
            'phone'          => $u->phone,
            'citizenship'    => $u->citizenship,
            'birth_date'     => $u->birth_date?->format('d.m.Y'),
            'avatar'         => $u->avatar ? url(Storage::url($u->avatar)) : null,
            'is_blocked'     => (bool) $u->deleted_at,
            'bookings_count' => $u->bookings_count ?? 0,
            'reviews_count'  => $u->reviews_count ?? 0,
            'created_at'     => $u->created_at?->format('d.m.Y'),
        ];
    }
}