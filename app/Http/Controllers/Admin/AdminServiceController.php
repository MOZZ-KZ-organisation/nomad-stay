<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class AdminServiceController extends Controller
{
    /**
     * GET /admin-api/services
     * Справочник доп. услуг отеля. ?is_active=1 — только активные (для выбора в брони).
     */
    public function index(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        $query = Service::where('hotel_id', $hotelId);

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return response()->json([
            'data' => $query->orderBy('title')->get()->map(fn ($s) => $this->format($s)),
        ]);
    }

    public function store(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'price' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $service = Service::create([...$data, 'hotel_id' => $hotelId]);

        return response()->json([
            'message' => 'Услуга добавлена',
            'data' => $this->format($service),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $hotelId = $this->resolveHotelId($request);
        $service = Service::where('hotel_id', $hotelId)->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'category' => 'nullable|string|max:100',
            'price' => 'sometimes|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        // Меняем только справочную цену — уже созданные booking_services её не наследуют (см. Payment/BookingService).
        $service->update($data);

        return response()->json([
            'message' => 'Услуга обновлена',
            'data' => $this->format($service->fresh()),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $hotelId = $this->resolveHotelId($request);
        $service = Service::where('hotel_id', $hotelId)->findOrFail($id);

        if ($service->bookingServices()->exists()) {
            // Не удаляем, если услуга уже использовалась в брони — иначе сломается история.
            $service->update(['is_active' => false]);
            return response()->json(['message' => 'Услуга уже использовалась в бронях — деактивирована, а не удалена']);
        }

        $service->delete();
        return response()->json(['message' => 'Услуга удалена']);
    }

    protected function format(Service $s): array
    {
        return [
            'id' => $s->id,
            'title' => $s->title,
            'category' => $s->category,
            'price' => $s->price,
            'unit' => $s->unit,
            'is_active' => $s->is_active,
        ];
    }

    protected function resolveHotelId(Request $request): int
    {
        $user = $request->user();
        if ($user->isHotelManager()) {
            abort_if(!$user->managedHotel, 403, 'Отель не назначен');
            return $user->managedHotel->id;
        }
        abort_unless($request->filled('hotel_id'), 422, 'Укажите hotel_id');
        return (int) $request->hotel_id;
    }
}