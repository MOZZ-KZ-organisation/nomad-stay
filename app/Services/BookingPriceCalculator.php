<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\RateRule;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Единая точка расчёта стоимости проживания.
 * Используется и публичным /bookings/price-details, и админкой при
 * создании/редактировании брони — чтобы итоговая цена и её расшифровка
 * считались бэкендом одинаково в обоих местах.
 */
class BookingPriceCalculator
{
    /**
     * @param Room $room
     * @param Carbon $plannedCheckIn
     * @param Carbon $plannedCheckOut
     * @param Collection<int,array{price:int,quantity:int}>|null $services доп. услуги [{price, quantity}, ...]
     * @return array{nights:int,accommodation:float,early_check_in:float,late_check_out:float,services:float,total:float,currency:string}
     */
    public function calculate(
        Room $room,
        Carbon $plannedCheckIn,
        Carbon $plannedCheckOut,
        ?Collection $services = null
    ): array {
        $hotel = $room->hotel;

        $nights = $plannedCheckIn->copy()->startOfDay()
            ->diffInDays($plannedCheckOut->copy()->startOfDay());
        $nights = max(1, $nights);

        $accommodation = $room->price * $nights;
        $earlyCheckIn = $this->calcWindowCharge($hotel, $room, $plannedCheckIn, 'early_check_in');
        $lateCheckOut = $this->calcWindowCharge($hotel, $room, $plannedCheckOut, 'late_check_out');
        $servicesTotal = $services
            ? $services->sum(fn ($s) => ($s['price'] ?? 0) * ($s['quantity'] ?? 1))
            : 0;

        $total = $accommodation + $earlyCheckIn + $lateCheckOut + $servicesTotal;

        return [
            'nights' => $nights,
            'accommodation' => (float) $accommodation,
            'early_check_in' => (float) $earlyCheckIn,
            'late_check_out' => (float) $lateCheckOut,
            'services' => (float) $servicesTotal,
            'total' => (float) $total,
            'currency' => 'KZT',
        ];
    }

    /**
     * Доплата за ранний заезд / поздний выезд.
     * Логика: если время заезда >= стандартного check-in — доплаты нет.
     * Если время выезда <= стандартного check-out — доплаты нет.
     * Иначе ищем подходящее правило rate_rules по временному окну.
     */
    protected function calcWindowCharge(?Hotel $hotel, Room $room, Carbon $moment, string $type): float
    {
        if (!$hotel) {
            return 0;
        }

        if ($type === 'early_check_in') {
            $standard = $hotel->standard_check_in_time;
            if (!$standard) {
                return 0;
            }
            $standardAt = Carbon::parse($moment->format('Y-m-d') . ' ' . $standard);
            // Заехал в стандартное время или позже — доплаты за ранний заезд нет.
            if ($moment->greaterThanOrEqualTo($standardAt)) {
                return 0;
            }
        } else { // late_check_out
            $standard = $hotel->standard_check_out_time;
            if (!$standard) {
                return 0;
            }
            $standardAt = Carbon::parse($moment->format('Y-m-d') . ' ' . $standard);
            // Выехал в стандартное время или раньше — доплаты за поздний выезд нет.
            if ($moment->lessThanOrEqualTo($standardAt)) {
                return 0;
            }
        }

        $time = $moment->format('H:i:s');

        $rule = RateRule::where('hotel_id', $hotel->id)
            ->where('type', $type)
            ->where('is_active', true)
            ->where(function ($q) use ($time) {
                $q->whereNull('from_time')->orWhere('from_time', '<=', $time);
            })
            ->where(function ($q) use ($time) {
                $q->whereNull('to_time')->orWhere('to_time', '>', $time);
            })
            ->orderBy('from_time')
            ->first();

        if (!$rule) {
            return 0;
        }

        return $rule->calc_type === 'percent'
            ? round($room->price * (float) $rule->value / 100)
            : (float) $rule->value;
    }
}