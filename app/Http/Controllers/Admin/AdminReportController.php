<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    /**
     * GET /admin-api/reports/revenue
     * Отчёт по выручке за период (по дням/месяцам).
     */
    public function revenue(Request $request)
    {
        $user      = $request->user();
        $isManager = $user->isHotelManager();
        $hotelId   = $isManager ? $user->managedHotel?->id : $request->hotel_id;

        $from   = Carbon::parse($request->get('from', now()->startOfMonth()))->startOfDay();
        $to     = Carbon::parse($request->get('to', now()->endOfMonth()))->endOfDay();
        $group  = $request->get('group', 'day'); // day | month

        $query = Booking::query()
            ->whereIn('status', ['booked', 'checked_in', 'checked_out'])
            ->whereBetween('created_at', [$from, $to]);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $format = $group === 'month' ? '%Y-%m' : '%Y-%m-%d';

        $data = $query->select([
            DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
            DB::raw('SUM(total_price) as revenue'),
            DB::raw('COUNT(*) as bookings_count'),
        ])
        ->groupBy('period')
        ->orderBy('period')
        ->get();

        // Итоговая сводка
        $summary = [
            'total_revenue'   => $data->sum('revenue'),
            'total_bookings'  => $data->sum('bookings_count'),
            'avg_per_booking' => $data->sum('bookings_count') > 0
                ? round($data->sum('revenue') / $data->sum('bookings_count'), 2)
                : 0,
        ];

        return response()->json(['data' => $data, 'summary' => $summary]);
    }

    /**
     * GET /admin-api/reports/occupancy
     * Отчёт по заполняемости номеров.
     */
    public function occupancy(Request $request)
    {
        $user      = $request->user();
        $isManager = $user->isHotelManager();
        $hotelId   = $isManager ? $user->managedHotel?->id : $request->hotel_id;

        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to   = Carbon::parse($request->get('to', now()->endOfMonth()));

        $query = Booking::query()
            ->whereIn('status', ['booked', 'checked_in', 'checked_out'])
            ->where('end_date', '>=', $from)
            ->where('start_date', '<=', $to);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $bookings = $query->select(['room_id', 'start_date', 'end_date', 'hotel_id'])->get();

        // Считаем занятые ночи на каждый номер
        $roomNights = [];
        foreach ($bookings as $b) {
            $start  = max($from, Carbon::parse($b->start_date));
            $end    = min($to, Carbon::parse($b->end_date));
            $nights = $start->diffInDays($end);
            $roomNights[$b->room_id] = ($roomNights[$b->room_id] ?? 0) + $nights;
        }

        $totalDays = $from->diffInDays($to) + 1;

        return response()->json([
            'data' => [
                'period_days'     => $totalDays,
                'rooms_data'      => collect($roomNights)->map(fn($nights, $roomId) => [
                    'room_id'        => $roomId,
                    'occupied_nights' => $nights,
                    'occupancy_rate'  => $totalDays > 0 ? round($nights / $totalDays * 100, 1) : 0,
                ])->values(),
                'avg_occupancy'   => count($roomNights) > 0
                    ? round(collect($roomNights)->avg() / $totalDays * 100, 1)
                    : 0,
            ],
        ]);
    }

    /**
     * GET /admin-api/reports/bookings-by-source
     * Распределение бронирований по источникам (сайт, booking.com и т.д.).
     */
    public function bySource(Request $request)
    {
        $user      = $request->user();
        $isManager = $user->isHotelManager();
        $hotelId   = $isManager ? $user->managedHotel?->id : $request->hotel_id;

        $from = $request->get('from', now()->startOfMonth());
        $to   = $request->get('to', now()->endOfMonth());

        $query = Booking::query()
            ->whereBetween('created_at', [$from, $to]);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $data = $query->select([
            'source',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total_price) as revenue'),
        ])
        ->groupBy('source')
        ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /admin-api/reports/reviews
     * Статистика по отзывам.
     */
    public function reviews(Request $request)
    {
        $user      = $request->user();
        $isManager = $user->isHotelManager();
        $hotelId   = $isManager ? $user->managedHotel?->id : $request->hotel_id;
 
        $query = Review::query();
        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }
 
        $summary = [
            'total'      => (clone $query)->count(),
            'avg_rating' => round((clone $query)->avg('rating') ?? 0, 2),
            'by_rating'  => (clone $query)->select('rating', DB::raw('count(*) as count'))
                ->groupBy('rating')
                ->orderBy('rating')
                ->get(),
        ];
 
        $latest = (clone $query)->with(['user:id,name', 'hotel:id,title'])
            ->latest()->limit(10)->get()
            ->map(fn($r) => [
                'id'         => $r->id,
                'rating'     => $r->rating,
                'comment'    => $r->comment,
                'user'       => $r->user?->name,
                'hotel'      => $r->hotel?->title,
                'created_at' => $r->created_at->format('d.m.Y'),
            ]);
 
        return response()->json(['summary' => $summary, 'latest' => $latest]);
    }
}