<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function show(Request $request, $id)
    {
        $booking = Booking::with([
            'hotel:id,title,slug,address,email',
            'room:id,title,price',
            'user:id,name,email,phone',
        ])->findOrFail($id);
        $this->authorizeHotel($request->user(), $booking->hotel_id);
        return response()->json(['data' => $this->formatBooking($booking, true)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id'          => 'required|exists:rooms,id',
            'start_date'       => 'required|date|after_or_equal:today',
            'end_date'         => 'required|date|after:start_date',
            'guests'           => 'required|integer|min:1',
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'email'            => 'required|email',
            'phone'            => 'nullable|string|max:30',
            'country'          => 'nullable|string|max:100',
            'is_business_trip' => 'boolean',
            'special_requests' => 'nullable|string|max:1000',
            'arrival_time'     => 'nullable|date_format:H:i',
            'source'           => 'nullable|string|in:site,booking.com,manual,phone',
            'status'           => 'nullable|string|in:booked,checked_in',
            'is_paid'          => 'boolean',
        ]);
        $room = Room::findOrFail($data['room_id']);
        $user = $request->user();
        if ($user->isHotelManager()) {
            $this->authorizeHotel($user, $room->hotel_id);
        }
        $bookedCount = Booking::where('room_id', $room->id)
            ->whereIn('status', ['booked', 'checked_in'])
            ->where('end_date', '>', $data['start_date'])
            ->where('start_date', '<', $data['end_date'])
            ->count();
        if ($bookedCount >= $room->stock) {
            return response()->json(['message' => 'Номер недоступен на выбранные даты'], 422);
        }
        $nights    = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date']));
        $basePrice = $room->price * $nights;
        $tax       = $basePrice * env('BOOKING_TAX_RATE', 0.0);
        $booking = Booking::create(array_merge($data, [
            'hotel_id'         => $room->hotel_id,
            'price_for_period' => $basePrice,
            'tax'              => $tax,
            'total_price'      => $basePrice + $tax,
            'status'           => $data['status'] ?? 'booked',
            'source'           => $data['source'] ?? 'manual',
            'type'             => 'booking',
        ]));
        return response()->json([
            'message' => 'Бронирование создано',
            'data'    => $this->formatBooking($booking->load(['hotel', 'room'])),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeHotel($request->user(), $booking->hotel_id);
        $data = $request->validate([
            'status'     => 'sometimes|string|in:booked,checked_in,checked_out,cancelled',
            'is_paid'    => 'sometimes|boolean',
            'start_date' => 'sometimes|date',
            'end_date'   => 'sometimes|date|after:start_date',
            'special_requests' => 'sometimes|nullable|string|max:1000',
        ]);
        $booking->update($data);
        return response()->json([
            'message' => 'Бронирование обновлено',
            'data'    => $this->formatBooking($booking->fresh(['hotel', 'room'])),
        ]);
    }

    public function calendar(Request $request)
    {
        $user = $request->user();
        $isManager = $user->isHotelManager();
        $start = Carbon::parse($request->get('start', Carbon::today()))->startOfDay();
        $end   = Carbon::parse($request->get('end', $start->copy()->addDays(30)))->endOfDay();
        $roomsQuery = Room::with('hotel:id,title');
        if ($isManager) {
            $roomsQuery->where('hotel_id', $user->managedHotel?->id);
        } elseif ($request->filled('hotel_id')) {
            $roomsQuery->where('hotel_id', $request->hotel_id);
        }
        if ($request->filled('room_type')) {
            $roomsQuery->where('title', 'like', '%' . $request->room_type . '%');
        }
        $rooms = $roomsQuery->get();
        $bookings = Booking::whereIn('room_id', $rooms->pluck('id'))
            ->where('end_date', '>=', $start)
            ->where('start_date', '<=', $end)
            ->whereNotIn('status', ['cancelled'])
            ->when($request->filled('source'), fn($q) => $q->where('source', $request->source))
            ->when($request->filled('is_paid'), fn($q) => $q->where('is_paid', (bool) $request->is_paid))
            ->get();
        return response()->json([
            'rooms' => $rooms->map(fn($room) => [
                'id'       => $room->id,
                'title'    => $room->title,
                'hotel_id' => $room->hotel_id,
                'hotel'    => $room->hotel?->title,
                'stock'    => $room->stock,
            ]),
            'bookings' => $bookings->map(fn($b) => [
                'id'             => $b->id,
                'booking_number' => $b->booking_number,
                'room_id'        => $b->room_id,
                'guest_name'     => trim("{$b->first_name} {$b->last_name}"),
                'start_date'     => $b->start_date->format('Y-m-d'),
                'end_date'       => $b->end_date->format('Y-m-d'),
                'status'         => $b->status,
                'is_paid'        => $b->is_paid,
                'total_price'    => $b->total_price,
                'color'          => $b->color,
                'source'         => $b->source,
                'guests'         => $b->guests,
                'arrival_time'   => $b->arrival_time?->format('H:i'),
                'special_requests' => $b->special_requests,
            ]),
        ]);
    }

    protected function formatBooking(Booking $b, bool $full = false): array
    {
        $data = [
            'id'             => $b->id,
            'booking_number' => $b->booking_number,
            'status'         => $b->status,
            'is_paid'        => (bool) $b->is_paid,
            'source'         => $b->source,
            'type'           => $b->type,
            'color'          => $b->color,
            'start_date'     => $b->start_date?->format('Y-m-d'),
            'end_date'       => $b->end_date?->format('Y-m-d'),
            'nights'         => $b->start_date && $b->end_date
                ? $b->start_date->diffInDays($b->end_date) : null,
            'guests'         => $b->guests,
            'price_for_period' => $b->price_for_period,
            'tax'            => $b->tax,
            'total_price'    => $b->total_price,
            'hotel'          => $b->hotel ? ['id' => $b->hotel->id, 'title' => $b->hotel->title] : null,
            'room'           => $b->room  ? ['id' => $b->room->id,  'title' => $b->room->title]  : null,
            'guest' => [
                'first_name'       => $b->first_name,
                'last_name'        => $b->last_name,
                'email'            => $b->email,
                'phone'            => $b->phone,
                'country'          => $b->country,
                'is_business_trip' => (bool) $b->is_business_trip,
                'special_requests' => $b->special_requests,
                'arrival_time'     => $b->arrival_time?->format('H:i'),
            ],
            'created_at' => $b->created_at?->format('d.m.Y H:i'),
        ];
        if ($full) {
            $data['user'] = $b->user ? [
                'id'    => $b->user->id,
                'name'  => $b->user->name,
                'email' => $b->user->email,
                'phone' => $b->user->phone,
            ] : null;
        }
        return $data;
    }

    protected function authorizeHotel($user, int $hotelId): void
    {
        if ($user->isHotelManager() && $user->managedHotel?->id !== $hotelId) {
            abort(403, 'Нет доступа к этому отелю');
        }
    }
}