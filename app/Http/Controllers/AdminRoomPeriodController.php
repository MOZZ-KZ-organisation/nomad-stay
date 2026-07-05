<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomPeriod;
use Illuminate\Http\Request;

class AdminRoomPeriodController extends Controller
{
    /**
     * GET /admin-api/rooms/{roomId}/periods
     * Список периодов номера (уборка/ремонт/свободен).
     * Используется для отображения на календаре вместе с бронями.
     */
    public function index(Request $request, $roomId)
    {
        $room = Room::findOrFail($roomId);
        $this->authorizeHotel($request->user(), $room->hotel_id);

        $query = RoomPeriod::where('room_id', $roomId);

        // Фильтр по периоду — для календаря
        if ($request->filled('start')) {
            $query->where('end_date', '>=', $request->start);
        }
        if ($request->filled('end')) {
            $query->where('start_date', '<=', $request->end);
        }

        $periods = $query->orderBy('start_date')->get();

        return response()->json([
            'data' => $periods->map(fn($p) => $this->formatPeriod($p)),
        ]);
    }

    /**
     * POST /admin-api/rooms/{roomId}/periods
     * Задать период статуса (уборка/ремонт/свободен).
     */
    public function store(Request $request, $roomId)
    {
        $room = Room::findOrFail($roomId);
        $this->authorizeHotel($request->user(), $room->hotel_id);

        $data = $request->validate([
            'status'     => 'required|in:free,cleaning,maintenance',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'comment'    => 'nullable|string|max:500',
        ]);

        $period = RoomPeriod::create([
            ...$data,
            'room_id'  => $room->id,
            'hotel_id' => $room->hotel_id,
        ]);

        return response()->json([
            'message' => 'Период сохранён',
            'data'    => $this->formatPeriod($period),
        ], 201);
    }

    /**
     * PATCH /admin-api/rooms/{roomId}/periods/{id}
     * Обновить период.
     */
    public function update(Request $request, $roomId, $id)
    {
        $room   = Room::findOrFail($roomId);
        $this->authorizeHotel($request->user(), $room->hotel_id);

        $period = RoomPeriod::where('room_id', $roomId)->findOrFail($id);

        $data = $request->validate([
            'status'     => 'sometimes|in:free,cleaning,maintenance',
            'start_date' => 'sometimes|date',
            'end_date'   => 'sometimes|date|after_or_equal:start_date',
            'comment'    => 'nullable|string|max:500',
        ]);

        $period->update($data);

        return response()->json([
            'message' => 'Период обновлён',
            'data'    => $this->formatPeriod($period->fresh()),
        ]);
    }

    /**
     * DELETE /admin-api/rooms/{roomId}/periods/{id}
     * Удалить период.
     */
    public function destroy(Request $request, $roomId, $id)
    {
        $room = Room::findOrFail($roomId);
        $this->authorizeHotel($request->user(), $room->hotel_id);

        $period = RoomPeriod::where('room_id', $roomId)->findOrFail($id);
        $period->delete();

        return response()->json(['message' => 'Период удалён']);
    }

    // -------------------------------------------------------------------------

    protected function formatPeriod(RoomPeriod $p): array
    {
        return [
            'id'         => $p->id,
            'room_id'    => $p->room_id,
            'hotel_id'   => $p->hotel_id,
            'status'     => $p->status,
            'color'      => $p->color,
            'start_date' => $p->start_date->format('Y-m-d'),
            'end_date'   => $p->end_date->format('Y-m-d'),
            'comment'    => $p->comment,
            'created_at' => $p->created_at->format('d.m.Y H:i'),
        ];
    }

    protected function authorizeHotel($user, int $hotelId): void
    {
        if ($user->isHotelManager() && $user->managedHotel?->id !== $hotelId) {
            abort(403, 'Нет доступа к этому отелю');
        }
    }
}