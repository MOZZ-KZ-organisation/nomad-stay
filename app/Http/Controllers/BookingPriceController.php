<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingPriceRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Service;
use App\Services\BookingPriceCalculator;
use Illuminate\Support\Collection;

class BookingPriceController extends Controller
{
    /**
     * GET /bookings/price-details
     * Сколько будет стоить бронь и из чего складывается сумма — до сохранения.
     * Считает тот же BookingPriceCalculator, что и создание брони, поэтому сумма
     * в превью совпадает с сохранённой. Проживание считается по ночам (тарифы на
     * период по датам или базовая цена номера); расшифровка — в price_breakdown.
     */
    public function show(BookingPriceRequest $request, BookingPriceCalculator $calculator)
    {
        $room = Room::with('hotel')->findOrFail($request->room_id);

        $query = Booking::where('room_id', $room->id)
            ->whereIn('status', ['booked', 'checked_in'])
            ->where('end_date', '>', $request->start_date)
            ->where('start_date', '<', $request->end_date);
        if ($request->filled('booking_id')) {
            $query->where('id', '!=', $request->booking_id);
        }
        $bookedCount = $query->count();
        $available = $bookedCount < $room->stock;

        // Собственная бронь гостя (при изменении дат): уже посчитанные ночи сохраняют
        // свою цену, а услуги брони входят в итог — как и при сохранении новых дат.
        $existing = null;
        if ($request->filled('booking_id')) {
            $candidate = Booking::with('services')->find($request->booking_id);
            if ($candidate && $candidate->user_id === $request->user()->id && $candidate->room_id === $room->id) {
                $existing = $candidate;
            }
        }

        // Плановые дата+время заезда/выезда — время по умолчанию берём
        // стандартное для отеля, если фронт не передал arrival_time/departure_time.
        [$plannedCheckIn, $plannedCheckOut] = $calculator->plannedMoments(
            $room,
            $request->start_date,
            $request->end_date,
            $request->arrival_time,
            $request->departure_time,
            $existing
        );

        if ($request->has('services') || !$existing) {
            $services = $this->resolveServices($request->input('services', []), $room->hotel_id);
        } else {
            $services = $existing->services->map(fn ($s) => ['price' => $s->price, 'quantity' => $s->quantity]);
        }

        $breakdown = $calculator->calculate(
            $room,
            $plannedCheckIn,
            $plannedCheckOut,
            $services,
            $existing ? $existing->lockedNights() : []
        );

        $payload = $calculator->previewPayload($room, $breakdown);
        $payload['total_price'] = $available ? $payload['total_price'] : null;

        return response()->json(array_merge(
            ['available' => $available],
            $payload,
            ['cancellation_fee' => $room->hotel->cancellation_fee]
        ));
    }

    protected function resolveServices(array $items, int $hotelId): Collection
    {
        if (empty($items)) {
            return collect();
        }
        $ids = collect($items)->pluck('service_id');
        $services = Service::where('hotel_id', $hotelId)->whereIn('id', $ids)->get()->keyBy('id');

        return collect($items)->map(function ($item) use ($services) {
            $service = $services->get($item['service_id']);
            return [
                'price' => $service?->price ?? 0,
                'quantity' => $item['quantity'] ?? 1,
            ];
        });
    }
}
