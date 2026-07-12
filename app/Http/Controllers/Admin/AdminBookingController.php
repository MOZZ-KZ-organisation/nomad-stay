<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomPeriod;
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
            // Если передан user_id — данные гостя подтянутся автоматически
            'user_id'          => 'nullable|exists:users,id',
            'first_name'       => 'required_without:user_id|string|max:100',
            'last_name'        => 'required_without:user_id|string|max:100',
            'email'            => 'required_without:user_id|email',
            'phone'            => 'nullable|string|max:30',
            'country'          => 'nullable|string|max:100',
            'is_business_trip' => 'boolean',
            'special_requests' => 'nullable|string|max:1000',
            'arrival_time'     => 'nullable|date_format:H:i',
            'source'           => 'nullable|string|in:site,booking.com,manual,phone',
            'status'           => 'nullable|string|in:booked,checked_in',
            'is_paid'          => 'boolean',
        ]);
        // Если выбран существующий гость — подтягиваем его данные
        if (!empty($data['user_id'])) {
            $guest = \App\Models\User::findOrFail($data['user_id']);
            $nameParts = explode(' ', $guest->name, 2);
            $data['first_name'] = $data['first_name'] ?? $nameParts[0];
            $data['last_name']  = $data['last_name']  ?? ($nameParts[1] ?? '');
            $data['email']      = $data['email']       ?? $guest->email;
            $data['phone']      = $data['phone']       ?? $guest->phone;
        }
        $room = Room::findOrFail($data['room_id']);
        $user = $request->user();
        // Менеджер может создавать только для своего отеля
        if ($user->isHotelManager()) {
            $this->authorizeHotel($user, $room->hotel_id);
        }
        // Проверка доступности
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

    /**
     * GET /admin-api/bookings
     * Список бронирований с пагинацией для табличного вида.
     */
    public function index(Request $request)
    {
        $user      = $request->user();
        $isManager = $user->isHotelManager();
 
        $query = Booking::with(['hotel:id,title', 'room:id,title'])
            ->latest();
 
        if ($isManager) {
            $query->where('hotel_id', $user->managedHotel?->id);
        } elseif ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }
 
        if ($request->filled('status')) {
            $query->whereIn('status', explode(',', $request->status));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('is_paid')) {
            $query->where('is_paid', filter_var($request->is_paid, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('start_date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('booking_number', 'like', "%{$s}%")
                ->orWhere('first_name',   'like', "%{$s}%")
                ->orWhere('last_name',    'like', "%{$s}%")
                ->orWhere('email',        'like', "%{$s}%")
                ->orWhere('phone',        'like', "%{$s}%")
            );
        }
 
        $bookings = $query->paginate($request->get('per_page', 20));
 
        return response()->json([
            'data' => $bookings->map(fn($b) => $this->formatBooking($b)),
            'meta' => [
                'total'        => $bookings->total(),
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'per_page'     => $bookings->perPage(),
            ],
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
        $periods = RoomPeriod::whereIn('room_id', $rooms->pluck('id'))
            ->where('end_date', '>=', $start)
            ->where('start_date', '<=', $end)
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
            'periods' => $periods->map(fn($p) => [
                'id'         => $p->id,
                'room_id'    => $p->room_id,
                'status'     => $p->status,
                'color'      => $p->color,
                'start_date' => $p->start_date->format('Y-m-d'),
                'end_date'   => $p->end_date->format('Y-m-d'),
                'comment'    => $p->comment,
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