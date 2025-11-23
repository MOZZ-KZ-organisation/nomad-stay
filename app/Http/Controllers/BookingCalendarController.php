<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingCalendarController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->get('start_date', now()->startOfMonth()->toDateString());
        $days = 30;
        $dates = collect();
        for ($i=0; $i<$days; $i++) {
            $dates->push(Carbon::parse($start)->addDays($i));
        }
        $rooms = Room::with(['hotel'])->get();
        $bookings = Booking::where('end_date', '>=', $dates->first())
            ->where('start_date', '<=', $dates->last())
            ->get();
        return view('vendor.voyager.bookings', compact(
            'rooms',
            'dates',
            'bookings'
        ));
    }
}
