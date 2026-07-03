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
        $user      = $request->user();
        $isManager = $user->isHotelManager();
        $hotelId   = $user->managedHotel?->id;
        $search    = $request->get('search');
 
        if ($isManager) {
            // 1. Зарегистрированные гости отеля
            $registeredIds = Booking::where('hotel_id', $hotelId)
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id');
            $registeredQuery = User::withCount(['bookings', 'reviews'])
                ->whereIn('id', $registeredIds);
            if ($search) {
                $registeredQuery->where(fn($q) => $q
                    ->where('name',  'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                );
            }
            $registered = $registeredQuery->get()->map(fn($u) => $this->formatGuest($u));
 
            // 2. Анонимные гости (user_id = null) — уникальные по email
            $anonymousQuery = Booking::where('hotel_id', $hotelId)
                ->whereNull('user_id')
                ->select('first_name', 'last_name', 'email', 'phone', 'country')
                ->groupBy('email', 'first_name', 'last_name', 'phone', 'country');
 
            if ($search) {
                $anonymousQuery->where(fn($q) => $q
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name',  'like', "%{$search}%")
                    ->orWhere('email',      'like', "%{$search}%")
                    ->orWhere('phone',      'like', "%{$search}%")
                );
            }
            $anonymous = $anonymousQuery->get()->map(fn($b) => $this->formatAnonymousGuest($b, $hotelId));
 
            // Объединяем и пагинируем вручную
            $all     = $registered->concat($anonymous)->sortByDesc('created_at')->values();
            $perPage = $request->get('per_page', 20);
            $page    = $request->get('page', 1);
            $paged   = $all->forPage($page, $perPage);
 
            return response()->json([
                'data' => $paged->values(),
                'meta' => [
                    'total'        => $all->count(),
                    'current_page' => (int) $page,
                    'last_page'    => (int) ceil($all->count() / $perPage),
                ],
            ]);
        }
 
        // Admin — только зарегистрированные пользователи
        $query = User::withCount(['bookings', 'reviews'])
            ->where(fn($q) => $q
                ->whereHas('role', fn($r) => $r->where('name', 'user'))
                ->orWhereNull('role_id')
            )
            ->latest();
 
        if ($search) {
            $query->where(fn($q) => $q
                ->where('name',  'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
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
     * Manager видит только гостя своего отеля.
     */
    public function show(Request $request, $id)
    {
        $user      = $request->user();
        $isManager = $user->isHotelManager();
        if ($isManager) {
            // Проверяем что гость хотя бы раз бронировал отель менеджера
            $hasBooking = Booking::where('hotel_id', $user->managedHotel?->id)
                ->where('user_id', $id)
                ->exists();
            abort_if(!$hasBooking, 403, 'Этот гость не бронировал ваш отель');
        }
        $guest = User::withCount(['bookings', 'reviews'])->findOrFail($id);
        // Менеджер видит только брони своего отеля, admin — все
        $bookingsQuery = Booking::where('user_id', $id)->with('hotel:id,title')->latest()->limit(10);
        if ($isManager) {
            $bookingsQuery->where('hotel_id', $user->managedHotel?->id);
        }
        $bookings = $bookingsQuery->get()->map(fn($b) => [
            'id'             => $b->id,
            'booking_number' => $b->booking_number,
            'hotel'          => $b->hotel?->title,
            'start_date'     => $b->start_date?->format('d.m.Y'),
            'end_date'       => $b->end_date?->format('d.m.Y'),
            'total_price'    => $b->total_price,
            'status'         => $b->status,
            'is_paid'        => $b->is_paid,
        ]);
        // Менеджер видит только отзывы своего отеля, admin — все
        $reviewsQuery = Review::where('user_id', $id)->with('hotel:id,title')->latest()->limit(5);
        if ($isManager) {
            $reviewsQuery->where('hotel_id', $user->managedHotel?->id);
        }
        $reviews = $reviewsQuery->get()->map(fn($r) => [
            'id'         => $r->id,
            'hotel'      => $r->hotel?->title,
            'rating'     => $r->rating,
            'comment'    => $r->comment,
            'created_at' => $r->created_at->format('d.m.Y'),
        ]);
        return response()->json([
            'data' => array_merge($this->formatGuest($guest), [
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
            // 'is_blocked'     => (bool) $u->deleted_at,
            'bookings_count' => $u->bookings_count ?? 0,
            'reviews_count'  => $u->reviews_count ?? 0,
        ];
    }
}