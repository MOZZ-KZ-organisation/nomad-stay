<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();
        $room = Room::findOrFail($data['room_id']);
        if ($room->stock < 1) {
            return response()->json(['message' => 'No rooms left'], 422);
        }
        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'hotel_id' => $data['hotel_id'],
            'room_id' => $data['room_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'guests' => $data['guests'],
            'price' => $data['price'],
            'status' => 'confirmed'
        ]);
        $room->decrement('stock');
        return new BookingResource($booking->load(['hotel', 'room']));
    }

    public function userBookings(Request $request)
    {
        $bookings = $request->user()->bookings()->with(['hotel', 'room'])->latest()->paginate(20);
        return BookingResource::collection($bookings);
    }
}
