<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingDatesRequest;
use App\Http\Resources\BookingMiniResource;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Notification;
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
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('end_date', '>', $data['start_date'])
            ->where('start_date', '<', $data['end_date'])
            ->count();
        if ($bookedCount >= $room->stock) {
            return response()->json(['message' => 'No rooms left for selected dates'], 422);
        }
        $nights = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date']));
        $basePrice = $room->price * $nights;
        $tax = $basePrice * env('BOOKING_TAX_RATE', 0.0);
        $totalPrice = $basePrice + $tax;
        $data += [
            'user_id' => $request->user()->id,
            'hotel_id' => $room->hotel_id,
            'price_for_period' => $basePrice,
            'tax' => $tax,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'type' => 'booking',
            'source' => 'site'
        ];
        $booking = Booking::create($data);
        $notification = Notification::create([
            'type' => 'booking_created',
            'title' => 'Новая бронь',
            'booking_id' => $booking->id,
            'source' => $booking->source
        ]);
        broadcast(new NewNotification($notification))->toOthers();
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

    public function cancel(Request $request, Booking $booking)
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Вы не можете отменить эту бронь'], 403);
        }
        if (now()->greaterThanOrEqualTo($booking->start_date)) {
            return response()->json(['message' => 'Нельзя отменить начавшееся бронирование'], 422);
        }
        $booking->update(['status' => 'cancelled']);
        $notification = Notification::create([
            'type' => 'booking_cancelled',
            'title' => 'Отменена бронь',
            'booking_id' => $booking->id,
            'source' => $booking->source
        ]);
        broadcast(new NewNotification($notification))->toOthers();
        return response()->json([
            'message' => 'Бронирование успешно отменено',
            'booking' => new BookingMiniResource($booking->load('hotel'))
        ]);
    }

    public function updateDates(UpdateBookingDatesRequest $request, Booking $booking)
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if (!in_array($booking->status, ['confirmed', 'pending'])) {
            return response()->json([
                'message' => 'Эту бронь нельзя изменить в текущем статусе'
            ], 422);
        }
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $room = $booking->room;
        $bookedCount = Booking::where('room_id', $room->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('id', '!=', $booking->id)
            ->where('end_date', '>', $startDate)
            ->where('start_date', '<', $endDate)
            ->count();
        if ($bookedCount >= $room->stock) {
            return response()->json([
                'message' => 'Номер недоступен на выбранные даты'
            ], 422);
        }
        $nights = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        $basePrice = $room->price * $nights;
        $tax = $basePrice * env('BOOKING_TAX_RATE', 0.0);
        $totalPrice = $basePrice + $tax;
        $booking->update([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price_for_period' => $basePrice,
            'tax' => $tax,
            'total_price' => $totalPrice,
        ]);
        $notification = Notification::create([
            'type' => 'booking_dates_updated',
            'title' => 'Даты брони изменены',
            'booking_id' => $booking->id,
            'source' => $booking->source
        ]);
        broadcast(new NewNotification($notification))->toOthers();
        return new BookingResource(
            $booking->load(['hotel', 'room'])
        );
    }
}
