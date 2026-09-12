<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRoomSegment;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminRoomTransferController extends Controller
{
    /**
     * GET /admin-api/bookings/{id}/room-transfers
     * История переселений гостя по номерам.
     */
    public function index(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeHotel($request->user(), $booking->hotel_id);

        $segments = $booking->roomSegments()->with('room:id,title')->get();

        // Если ни разу не переселяли — история состоит из одного "виртуального"
        // сегмента, соответствующего текущему room_id брони.
        if ($segments->isEmpty()) {
            $room = $booking->room;
            return response()->json([
                'data' => [[
                    'id' => null,
                    'room' => $room ? ['id' => $room->id, 'title' => $room->title] : null,
                    'check_in_at' => optional($booking->planned_check_in_at ?? $booking->start_date)->format('Y-m-d H:i'),
                    'check_out_at' => null,
                    'reason' => null,
                ]],
            ]);
        }

        return response()->json([
            'data' => $segments->map(fn (BookingRoomSegment $s) => $this->formatSegment($s)),
        ]);
    }

    /**
     * POST /admin-api/bookings/{id}/room-transfers
     * Переселить гостя в другой номер: закрывает текущий сегмент и открывает новый.
     */
    public function store(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeHotel($request->user(), $booking->hotel_id);

        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in_at' => 'nullable|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $newRoom = Room::findOrFail($data['room_id']);
        if ($newRoom->id === $booking->room_id) {
            return response()->json(['message' => 'Гость уже в этом номере'], 422);
        }
        // менеджер может переселять только в рамках своего отеля
        if ($newRoom->hotel_id !== $booking->hotel_id) {
            return response()->json(['message' => 'Номер принадлежит другому отелю'], 422);
        }

        $moment = $data['check_in_at'] ? Carbon::parse($data['check_in_at']) : now();

        // Проверка занятости нового номера на момент переселения.
        $occupied = Booking::where('room_id', $newRoom->id)
            ->where('id', '!=', $booking->id)
            ->whereIn('status', ['booked', 'checked_in'])
            ->where('start_date', '<=', $moment->toDateString())
            ->where('end_date', '>', $moment->toDateString())
            ->exists();
        if ($occupied) {
            return response()->json(['message' => 'Номер уже занят на это время'], 422);
        }

        // Закрываем текущий активный сегмент (если история уже велась),
        // иначе создаём стартовый сегмент задним числом от начала брони.
        $currentSegment = $booking->roomSegments()->whereNull('check_out_at')->latest('check_in_at')->first();
        if ($currentSegment) {
            $currentSegment->update(['check_out_at' => $moment]);
        } else {
            BookingRoomSegment::create([
                'booking_id' => $booking->id,
                'room_id' => $booking->room_id,
                'check_in_at' => $booking->planned_check_in_at ?? $booking->start_date,
                'check_out_at' => $moment,
                'reason' => null,
                'created_by' => $request->user()->id,
            ]);
        }

        $newSegment = BookingRoomSegment::create([
            'booking_id' => $booking->id,
            'room_id' => $newRoom->id,
            'check_in_at' => $moment,
            'check_out_at' => null,
            'reason' => $data['reason'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        // Бронь всегда хранит "текущий" номер в room_id — карта заезда/чата и пр. на него завязаны.
        $booking->room_id = $newRoom->id;
        $booking->saveQuietly(); // не пересчитываем стоимость по датам — переселение цену не меняет

        return response()->json([
            'message' => 'Гость переселён',
            'data' => $this->formatSegment($newSegment->load('room:id,title')),
        ], 201);
    }

    protected function formatSegment(BookingRoomSegment $s): array
    {
        return [
            'id' => $s->id,
            'room' => $s->room ? ['id' => $s->room->id, 'title' => $s->room->title] : null,
            'check_in_at' => $s->check_in_at?->format('Y-m-d H:i'),
            'check_out_at' => $s->check_out_at?->format('Y-m-d H:i'),
            'reason' => $s->reason,
            'created_by' => $s->created_by,
            'created_at' => $s->created_at?->format('d.m.Y H:i'),
        ];
    }

    protected function authorizeHotel($user, int $hotelId): void
    {
        if ($user->isHotelManager() && $user->managedHotel?->id !== $hotelId) {
            abort(403, 'Нет доступа к этому отелю');
        }
    }
}