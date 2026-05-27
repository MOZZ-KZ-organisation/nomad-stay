<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingPdfController extends Controller
{
    public function download(Request $request, Booking $booking)
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'У вас нет доступа к этой брони'
            ], 403);
        }
        $booking->load(['hotel.city.country', 'room', 'user']);
        $nights = $booking->start_date->diffInDays($booking->end_date);
        $data = [
            'booking' => $booking,
            'nights' => $nights,
            'hotel' => $booking->hotel,
            'room' => $booking->room,
            'city' => $booking->hotel->city,
            'country' => $booking->hotel->city->country,
            'hotel_email' => $booking->hotel->email,
        ];
        return view('pdf.booking-confirmation', $data);
    }
}