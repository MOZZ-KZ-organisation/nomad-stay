<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class VoyagerHotelController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isHotelManager()) {
            $hotel = $user->managedHotel;

            if (!$hotel) {
                return redirect()->route('voyager.hotels.create')
                    ->with('message', 'Создайте ваш отель')
                    ->with('alert-type', 'info');
            }

            return redirect()->route('voyager.hotels.edit', $hotel->id);
        }

        // Для админа — редирект на стандартный Voyager список
        return redirect('/admin/hotels');
    }
}
