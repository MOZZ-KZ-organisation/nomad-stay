<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingPriceRequest;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingPriceController extends Controller
{
    public function show(BookingPriceRequest $request)
    {
        $room = Room::findOrFail($request->room_id);
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $nights = $start->diffInDays($end);
        $basePrice = $room->price_per_night;
        $priceForPeriod = $basePrice * $nights;
        $taxRate = env('BOOKING_TAX_RATE', 0.1);
        $tax = round($priceForPeriod * $taxRate);
        $totalPrice = $priceForPeriod + $tax;
        return response()->json([
            'room_id' => $room->id,
            'nights' => $nights,
            'price_per_night' => $basePrice,
            'price_for_period' => $priceForPeriod,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'total_price' => $totalPrice,
            'cancellation_fee' => $room->hotel->cancellation_fee
        ]);
    }
}
