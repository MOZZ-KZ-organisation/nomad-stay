<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminRoomController extends Controller
{
    /**
     * GET /admin-api/rooms
     * Список номеров (с фильтром по hotel_id).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Room::with(['hotel:id,title', 'images'])->withCount('bookings');

        if ($user->isHotelManager()) {
            $query->where('hotel_id', $user->managedHotel?->id);
        } elseif ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        $rooms = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $rooms->map(fn($r) => $this->formatRoom($r)),
            'meta' => [
                'total'        => $rooms->total(),
                'current_page' => $rooms->currentPage(),
                'last_page'    => $rooms->lastPage(),
            ],
        ]);
    }

    /**
     * GET /admin-api/rooms/{id}
     * Детали номера.
     */
    public function show(Request $request, $id)
    {
        $room = Room::with(['hotel:id,title', 'images'])->findOrFail($id);
        $user = $request->user();

        if ($user->isHotelManager() && $user->managedHotel?->id !== $room->hotel_id) {
            abort(403);
        }

        return response()->json(['data' => $this->formatRoom($room, true)]);
    }

    /**
     * POST /admin-api/rooms
     * Создать номер.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'hotel_id'    => 'required|exists:hotels,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity'    => 'required|integer|min:1',
            'beds'        => 'required|integer|min:1',
            'bathrooms'   => 'required|integer|min:0',
            'price'       => 'required|integer|min:0',
            'stock'       => 'required|integer|min:1',
        ]);

        if ($user->isHotelManager() && $user->managedHotel?->id !== (int) $data['hotel_id']) {
            abort(403);
        }

        $room = Room::create([
            ...$data,
            'slug' => Str::slug($data['title']),
        ]);

        return response()->json([
            'message' => 'Номер создан',
            'data'    => $this->formatRoom($room->load(['hotel:id,title', 'images']), true),
        ], 201);
    }

    /**
     * PATCH /admin-api/rooms/{id}
     * Обновить номер (в т.ч. цену — раздел "Цены").
     */
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $user = $request->user();

        if ($user->isHotelManager() && $user->managedHotel?->id !== $room->hotel_id) {
            abort(403);
        }

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'capacity'    => 'sometimes|integer|min:1',
            'beds'        => 'sometimes|integer|min:1',
            'bathrooms'   => 'sometimes|integer|min:0',
            'price'       => 'sometimes|integer|min:0',
            'stock'       => 'sometimes|integer|min:1',
        ]);

        $room->update($data);

        return response()->json([
            'message' => 'Номер обновлён',
            'data'    => $this->formatRoom($room->fresh(['hotel:id,title', 'images']), true),
        ]);
    }

    /**
     * DELETE /admin-api/rooms/{id}
     * Удалить номер.
     */
    public function destroy(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $user = $request->user();

        if ($user->isHotelManager() && $user->managedHotel?->id !== $room->hotel_id) {
            abort(403);
        }

        $room->delete();
        return response()->json(['message' => 'Номер удалён']);
    }

    /**
     * POST /admin-api/rooms/{id}/images
     * Загрузить фото номера.
     */
    public function uploadImages(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $user = $request->user();

        if ($user->isHotelManager() && $user->managedHotel?->id !== $room->hotel_id) {
            abort(403);
        }

        $request->validate([
            'images'     => 'required|array|min:1',
            'images.*'   => 'image|mimes:jpeg,png,webp|max:5120',
            'main_index' => 'nullable|integer',
        ]);

        $mainIndex = $request->get('main_index', 0);
        $uploaded  = [];

        foreach ($request->file('images') as $idx => $file) {
            $path  = $file->store('rooms', 'public');
            $image = $room->images()->create([
                'path'    => $path,
                'is_main' => ($idx === (int) $mainIndex),
            ]);
            $uploaded[] = [
                'id'      => $image->id,
                'url'     => url(Storage::url($path)),
                'is_main' => $image->is_main,
            ];
        }

        return response()->json(['message' => 'Фото загружены', 'data' => $uploaded], 201);
    }

    /**
     * DELETE /admin-api/rooms/{roomId}/images/{imageId}
     * Удалить фото номера.
     */
    public function deleteImage(Request $request, $roomId, $imageId)
    {
        $room  = Room::findOrFail($roomId);
        $user  = $request->user();

        if ($user->isHotelManager() && $user->managedHotel?->id !== $room->hotel_id) {
            abort(403);
        }

        $image = $room->images()->findOrFail($imageId);

        if ($image->path && Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }
        $image->delete();

        return response()->json(['message' => 'Фото удалено']);
    }

    // -------------------------------------------------------------------------

    protected function formatRoom(Room $r, bool $full = false): array
    {
        return [
            'id'          => $r->id,
            'hotel_id'    => $r->hotel_id,
            'hotel'       => $r->hotel ? ['id' => $r->hotel->id, 'title' => $r->hotel->title] : null,
            'title'       => $r->title,
            'slug'        => $r->slug,
            'description' => $full ? $r->description : null,
            'capacity'    => $r->capacity,
            'beds'        => $r->beds,
            'bathrooms'   => $r->bathrooms,
            'price'       => $r->price,
            'stock'       => $r->stock,
            'images'      => $r->images?->map(fn($i) => [
                'id'      => $i->id,
                'url'     => url(Storage::url($i->path)),
                'is_main' => $i->is_main,
            ]),
            'bookings_count' => $r->bookings_count ?? null,
            'created_at'  => $r->created_at?->format('d.m.Y'),
        ];
    }
}