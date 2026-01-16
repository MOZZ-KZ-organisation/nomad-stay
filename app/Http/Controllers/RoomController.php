<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotelShowRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;

class RoomController extends Controller
{
    public function show(HotelShowRequest $request, Room $room)
    {
        $room->load('images', 'hotel.amenities');
        return new RoomResource($room);
    }
}
