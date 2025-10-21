<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingMiniResource;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();
        $room = Room::findOrFail($data['room_id']);
        $bookedCount = Booking::where('room_id', $room->id)
            ->where('status', 'confirmed')
            ->where('end_date', '>', $data['start_date'])
            ->where('start_date', '<', $data['end_date'])
            ->count();
        if ($bookedCount >= $room->stock) {
            return response()->json(['message' => 'No rooms left for selected dates'], 422);
        }
        $nights = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date']));
        $totalPrice = $room->price * max(1, $nights);
        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'hotel_id' => $room->hotel_id,
            'room_id' => $data['room_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'guests' => $data['guests'],
            'price' => $totalPrice,
            'status' => 'confirmed'
        ]);
        return new BookingResource($booking->load(['hotel', 'room']));
    }

    public function userBookings(Request $request)
    {
        $bookings = $request->user()->bookings()->with('hotel')->latest()->paginate(10);
        return BookingMiniResource::collection($bookings);
    }

    public function show(Booking $booking)
    {
        $booking->load(['hotel', 'room']);
        return new BookingResource($booking);
    }
}
