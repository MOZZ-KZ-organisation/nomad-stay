<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingPriceRequest;
use App\Models\Booking;
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
        $query = Booking::where('room_id', $room->id)
            ->whereIn('status', ['booked', 'checked_in'])
            ->where('end_date', '>', $request->start_date)
            ->where('start_date', '<', $request->end_date);
        if ($request->filled('booking_id')) {
            $query->where('id', '!=', $request->booking_id);
        }
        $bookedCount = $query->count();
        $available = $bookedCount < $room->stock;
        $basePrice = $room->price;
        $priceForPeriod = $basePrice * $nights;
        $taxRate = env('BOOKING_TAX_RATE', 0.1);
        $tax = round($priceForPeriod * $taxRate);
        $totalPrice = $priceForPeriod + $tax;
        return response()->json([
            'available' => $available,
            'room_id' => $room->id,
            'nights' => $nights,
            'price_per_night' => $basePrice,
            'price_for_period' => $priceForPeriod,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'total_price' => $available ? $totalPrice : null,
            'cancellation_fee' => $room->hotel->cancellation_fee
        ]);
    }
}
