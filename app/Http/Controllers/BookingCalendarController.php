<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingCalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isManager = $user->isHotelManager();
        $managerHotelId = $isManager ? $user->managedHotel?->id : null;
        $startDate = $request->filled('start')
            ? Carbon::parse($request->start) : Carbon::today();
        $days = 18;
        $dates = collect();
        for ($i = 0; $i < $days; $i++) {
            $dates->push($startDate->copy()->addDays($i));
        }
        $roomsQuery = Room::with(['hotel']);
        // Менеджер видит только свой отель — жёстко, без возможности обхода
        if ($isManager) {
            $roomsQuery->where('hotel_id', $managerHotelId);
        } elseif ($request->filled('hotel_id')) {
            $roomsQuery->where('hotel_id', $request->hotel_id);
        }
        if ($request->filled('room_type')) {
            $roomsQuery->where('title', 'LIKE', '%' . $request->room_type . '%');
        }
        $rooms = $roomsQuery->get();
        $roomIds = $rooms->pluck('id');
        $bookingsQuery = Booking::whereIn('room_id', $roomIds) // только свои комнаты
            ->where('end_date', '>=', $dates->first())
            ->where('start_date', '<=', $dates->last())
            ->whereNotIn('status', ['cancelled']);
        if ($request->boolean('only_booked')) {
            $bookingsQuery->whereIn('status', ['booked', 'checked_in']);
        }
        if ($request->filled('source')) {
            $bookingsQuery->where('source', $request->source);
        }
        if ($request->payment_status === 'paid') {
            $bookingsQuery->where('is_paid', 1);
        }
        if ($request->payment_status === 'unpaid') {
            $bookingsQuery->where('is_paid', 0);
        }
        $bookings = $bookingsQuery->get();
        if ($request->boolean('only_booked')) {
            $bookedRoomIds = $bookings->pluck('room_id')->unique();
            $rooms = $rooms->whereIn('id', $bookedRoomIds);
        }
        // Менеджер видит только типы номеров своего отеля
        $roomTypes = Room::when($isManager, fn($q) => $q->where('hotel_id', $managerHotelId))
            ->select('title')->distinct()->pluck('title');
        $hotels = $isManager ? collect([$user->managedHotel]) : Hotel::all();
        return view('vendor.voyager.bookings', compact(
            'rooms',
            'dates',
            'bookings',
            'hotels',
            'roomTypes',
            'startDate'
        ));
    }
}
