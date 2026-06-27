<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Notification;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * GET /admin-api/dashboard
     * Сводная статистика для дашборда (раздел "События").
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isManager = $user->isHotelManager();
        $hotelId = $isManager ? $user->managedHotel?->id : null;

        $bookingsQuery = Booking::query();
        if ($hotelId) {
            $bookingsQuery->where('hotel_id', $hotelId);
        }

        $today = Carbon::today();

        // Бронирования по статусам
        $stats = [
            'total_bookings'      => (clone $bookingsQuery)->count(),
            'booked'              => (clone $bookingsQuery)->where('status', 'booked')->count(),
            'checked_in'          => (clone $bookingsQuery)->where('status', 'checked_in')->count(),
            'checked_out'         => (clone $bookingsQuery)->where('status', 'checked_out')->count(),
            'cancelled'           => (clone $bookingsQuery)->where('status', 'cancelled')->count(),
            'today_arrivals'      => (clone $bookingsQuery)->whereDate('start_date', $today)->whereIn('status', ['booked', 'checked_in'])->count(),
            'today_departures'    => (clone $bookingsQuery)->whereDate('end_date', $today)->whereIn('status', ['checked_in', 'checked_out'])->count(),
            'revenue_total'       => (clone $bookingsQuery)->whereIn('status', ['booked', 'checked_in', 'checked_out'])->sum('total_price'),
            'revenue_this_month'  => (clone $bookingsQuery)->whereIn('status', ['booked', 'checked_in', 'checked_out'])->whereMonth('created_at', $today->month)->whereYear('created_at', $today->year)->sum('total_price'),
        ];

        if (!$isManager) {
            $stats['total_hotels'] = Hotel::count();
            $stats['active_hotels'] = Hotel::where('is_active', true)->count();
            $stats['total_users'] = User::count();
            $stats['total_reviews'] = Review::count();
        }

        return response()->json(['data' => $stats]);
    }

    /**
     * GET /admin-api/notifications
     * Список уведомлений (события).
     */
    public function notifications(Request $request)
    {
        $notifications = Notification::with('booking')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $notifications->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'source'     => $n->source,
                'is_read'    => $n->is_read,
                'booking_id' => $n->booking_id,
                'booking_number' => $n->booking?->booking_number,
                'created_at' => $n->created_at->format('d.m.Y H:i'),
            ]),
            'meta' => [
                'total'        => $notifications->total(),
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'unread_count' => Notification::where('is_read', false)->count(),
            ],
        ]);
    }

    /**
     * PATCH /admin-api/notifications/{id}/read
     * Пометить уведомление как прочитанное.
     */
    public function markRead(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);
        return response()->json(['message' => 'Прочитано']);
    }

    /**
     * PATCH /admin-api/notifications/read-all
     * Пометить все уведомления как прочитанные.
     */
    public function markAllRead()
    {
        Notification::where('is_read', false)->update(['is_read' => true]);
        return response()->json(['message' => 'Все прочитаны']);
    }
}