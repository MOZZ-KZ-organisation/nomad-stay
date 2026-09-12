<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingPriceRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Service;
use App\Services\BookingPriceCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingPriceController extends Controller
{
    public function show(BookingPriceRequest $request, BookingPriceCalculator $calculator)
    {
        $room = Room::findOrFail($request->room_id);
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);

        $query = Booking::where('room_id', $room->id)
            ->whereIn('status', ['booked', 'checked_in'])
            ->where('end_date', '>', $request->start_date)
            ->where('start_date', '<', $request->end_date);
        if ($request->filled('booking_id')) {
            $query->where('id', '!=', $request->booking_id);
        }
        $bookedCount = $query->count();
        $available = $bookedCount < $room->stock;

        // Плановые дата+время заезда/выезда — время по умолчанию берём
        // стандартное для отеля, если фронт не передал arrival_time/departure_time.
        $plannedCheckIn = Carbon::parse($start->toDateString() . ' ' . ($request->arrival_time ?? $room->hotel?->standard_check_in_time ?? '14:00'));
        $plannedCheckOut = Carbon::parse($end->toDateString() . ' ' . ($request->departure_time ?? $room->hotel?->standard_check_out_time ?? '12:00'));

        $servicesInput = $this->resolveServices($request->input('services', []), $room->hotel_id);

        $breakdown = $calculator->calculate($room, $plannedCheckIn, $plannedCheckOut, $servicesInput);

        // tax считаем от суточной части (как и раньше), доплаты за
        // ранний/поздний и услуги налогом на этом шаге не облагаем —
        // при необходимости скорректируйте под правила вашего отеля.
        $taxRate = env('BOOKING_TAX_RATE', 0.1);
        $tax = round($breakdown['accommodation'] * $taxRate);
        $totalPrice = $breakdown['total'] + $tax;

        return response()->json([
            'available' => $available,
            'room_id' => $room->id,
            'nights' => $breakdown['nights'],
            'price_per_night' => $room->price,
            'price_for_period' => $breakdown['accommodation'],
            'early_check_in' => $breakdown['early_check_in'],
            'late_check_out' => $breakdown['late_check_out'],
            'services' => $breakdown['services'],
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'total_price' => $available ? $totalPrice : null,
            'cancellation_fee' => $room->hotel->cancellation_fee,
        ]);
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