<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class VoyagerRoomController extends VoyagerBaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user->isHotelManager()) {
            $hotel = $user->managedHotel;
            if (!$hotel) {
                return redirect()->route('voyager.hotels.index')
                    ->with('message', 'Сначала создайте отель')
                    ->with('alert-type', 'warning');
            }
            $request->merge(['hotel_id' => $hotel->id]);
        }
        return parent::index($request);
    }
}