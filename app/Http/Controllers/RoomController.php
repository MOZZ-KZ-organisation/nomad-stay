<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotelShowRequest;
use App\Http\Resources\RoomResource;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;

class RoomController extends Controller
{
    public function show(HotelShowRequest $request, Room $room)
    {
        $room->load('images', 'hotel');
        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);
        $bookedCount = Booking::where('room_id', $room->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('end_date', '>', $start)
            ->where('start_date', '<', $end)
            ->count();
        $room->available_stock = max(
            $room->stock - $bookedCount,
            0
        );
        return new RoomResource($room);
    }
}
