<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Service;
use Illuminate\Http\Request;

class AdminBookingServiceController extends Controller
{
    /**
     * GET /admin-api/bookings/{id}/services
     */
    public function index(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeHotel($request->user(), $booking->hotel_id);

        $items = $booking->services()->with('service:id,title,unit')->get();

        return response()->json([
            'data' => $items->map(fn ($i) => $this->format($i)),
            'services_amount' => (int) $items->sum('amount'),
        ]);
    }

    /**
     * POST /admin-api/bookings/{id}/services
     * Добавить услугу в бронь. Цена фиксируется из справочника на момент добавления.
     */
    public function store(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeHotel($request->user(), $booking->hotel_id);

        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'quantity' => 'required|integer|min:1',
            'comment' => 'nullable|string|max:255',
        ]);

        $service = Service::where('hotel_id', $booking->hotel_id)->findOrFail($data['service_id']);
        if (!$service->is_active) {
            return response()->json(['message' => 'Услуга неактивна'], 422);
        }

        $item = BookingService::create([
            'booking_id' => $booking->id,
            'service_id' => $service->id,
            'quantity' => $data['quantity'],
            'price' => $service->price, // снимок цены — не ссылка на справочник
            'comment' => $data['comment'] ?? null,
        ]);

        $this->recalcServicesAmount($booking);

        return response()->json([
            'message' => 'Услуга добавлена в бронь',
            'data' => $this->format($item->load('service:id,title,unit')),
        ], 201);
    }

    public function update(Request $request, $bookingId, $id)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeHotel($request->user(), $booking->hotel_id);
        $item = BookingService::where('booking_id', $bookingId)->findOrFail($id);

        $data = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'comment' => 'nullable|string|max:255',
        ]);
        // price сознательно нельзя менять через API — только пересоздать позицию.
        $item->update($data);

        $this->recalcServicesAmount($booking);

        return response()->json([
            'message' => 'Услуга обновлена',
            'data' => $this->format($item->fresh('service:id,title,unit')),
        ]);
    }

    public function destroy(Request $request, $bookingId, $id)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeHotel($request->user(), $booking->hotel_id);
        $item = BookingService::where('booking_id', $bookingId)->findOrFail($id);
        $item->delete();

        $this->recalcServicesAmount($booking);

        return response()->json(['message' => 'Услуга удалена из брони']);
    }

    /**
     * Пересчитывает services_amount и total_price брони после изменения списка услуг.
     */
    protected function recalcServicesAmount(Booking $booking): void
    {
        $servicesAmount = (int) $booking->services()->sum('amount');
        $booking->services_amount = $servicesAmount;
        $booking->total_price = $booking->price_for_period + $booking->tax
            + $booking->early_check_in_amount + $booking->late_check_out_amount
            + $servicesAmount;
        $booking->saveQuietly();
    }

    protected function format(BookingService $i): array
    {
        return [
            'id' => $i->id,
            'service' => $i->service ? [
                'id' => $i->service->id,
                'title' => $i->service->title,
                'unit' => $i->service->unit,
            ] : null,
            'quantity' => $i->quantity,
            'price' => $i->price,
            'amount' => $i->amount,
            'comment' => $i->comment,
        ];
    }

    protected function authorizeHotel($user, int $hotelId): void
    {
        if ($user->isHotelManager() && $user->managedHotel?->id !== $hotelId) {
            abort(403, 'Нет доступа к этому отелю');
        }
    }
}