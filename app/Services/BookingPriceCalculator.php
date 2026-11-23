<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RateRule;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Единая точка расчёта стоимости проживания.
 * Используется и публичным /bookings/price-details, и админкой, и сайтом при
 * создании/редактировании брони — чтобы итоговая цена и её расшифровка
 * считались бэкендом одинаково везде и сумма в превью совпадала с сохранённой.
 *
 * Стоимость проживания считается по ночам (см. NightlyPricing): тарифы на период
 * по датам (period_rates) → иначе базовая цена номера (rooms.price).
 */
class BookingPriceCalculator
{
    /**
     * @param Room $room
     * @param Carbon $plannedCheckIn
     * @param Carbon $plannedCheckOut
     * @param Collection<int,array{price:int,quantity:int}>|null $services доп. услуги [{price, quantity}, ...]
     * @param array $lockedNights ночи с зафиксированной ценой (см. Booking::lockedNights()):
     *        они не пересчитываются по текущим тарифам
     * @return array{
     *     nights:int,
     *     accommodation:float,
     *     price_breakdown:array,
     *     early_check_in:float,
     *     late_check_out:float,
     *     services:float,
     *     subtotal:float,
     *     tax_rate:float,
     *     tax:float,
     *     total:float,
     *     currency:string
     * } total = всё, что платит гость, включая налог; налог считается от стоимости проживания
     */
    public function calculate(
        Room $room,
        Carbon $plannedCheckIn,
        Carbon $plannedCheckOut,
        ?Collection $services = null,
        array $lockedNights = []
    ): array {
        $hotel = $room->hotel;

        $startDate = $plannedCheckIn->toDateString();
        $endDate = $plannedCheckOut->toDateString();

        $pricing = NightlyPricing::resolve(
            (int) $room->price,
            $startDate,
            $endDate,
            $this->activeRates($room, $startDate, $endDate),
            $lockedNights
        );

        $accommodation = $pricing['total'];

        // Доплата в процентах считается от цены той ночи, к которой «прилипает» ранний
        // заезд (первая ночь) или поздний выезд (последняя), а не от базовой цены номера.
        $earlyCheckIn = $this->calcWindowCharge($hotel, $plannedCheckIn, 'early_check_in', $pricing['first_night_price']);
        $lateCheckOut = $this->calcWindowCharge($hotel, $plannedCheckOut, 'late_check_out', $pricing['last_night_price']);
        $servicesTotal = $services
            ? $services->sum(fn ($s) => ($s['price'] ?? 0) * ($s['quantity'] ?? 1))
            : 0;

        $taxRate = (float) config('booking.tax_rate');
        $tax = round($accommodation * $taxRate);

        $subtotal = $accommodation + $earlyCheckIn + $lateCheckOut + $servicesTotal;

        return [
            'nights' => $pricing['nights'],
            'accommodation' => (float) $accommodation,
            'price_breakdown' => $pricing['lines'],
            'early_check_in' => (float) $earlyCheckIn,
            'late_check_out' => (float) $lateCheckOut,
            'services' => (float) $servicesTotal,
            'subtotal' => (float) $subtotal,
            'tax_rate' => $taxRate,
            'tax' => (float) $tax,
            'total' => (float) ($subtotal + $tax),
            'currency' => 'KZT',
        ];
    }

    /**
     * Тело ответа для превью цены — одно и то же для сайта и админки.
     */
    public function previewPayload(Room $room, array $breakdown): array
    {
        return [
            'room_id' => $room->id,
            'nights' => $breakdown['nights'],
            // Базовая цена номера. Фактическая цена каждой ночи — в price_breakdown.
            'price_per_night' => $room->price,
            'price_for_period' => $breakdown['accommodation'],
            'price_breakdown' => $breakdown['price_breakdown'],
            'early_check_in' => $breakdown['early_check_in'],
            'late_check_out' => $breakdown['late_check_out'],
            'services' => $breakdown['services'],
            'tax_rate' => $breakdown['tax_rate'],
            'tax' => $breakdown['tax'],
            'total_price' => $breakdown['total'],
            'currency' => $breakdown['currency'],
        ];
    }

    /**
     * Записывает рассчитанную стоимость в бронь (в том числе снимок посуточной
     * расшифровки). Услуги, уже привязанные к брони, учитываются через services_amount.
     * После этого модельные хуки не пересчитывают цену повторно.
     */
    public function applyToBooking(Booking $booking, array $breakdown, Carbon $plannedCheckIn, Carbon $plannedCheckOut): void
    {
        $booking->planned_check_in_at = $plannedCheckIn;
        $booking->planned_check_out_at = $plannedCheckOut;
        $booking->price_for_period = $breakdown['accommodation'];
        $booking->price_breakdown = $breakdown['price_breakdown'];
        $booking->tax = $breakdown['tax'];
        $booking->early_check_in_amount = $breakdown['early_check_in'];
        $booking->late_check_out_amount = $breakdown['late_check_out'];
        $booking->total_price = $breakdown['total'] - $breakdown['services'] + (float) ($booking->services_amount ?? 0);
        $booking->skipAutoPricing = true;
    }

    /**
     * Плановые дата+время заезда/выезда из дат брони + времени (переданного,
     * сохранённого в брони или стандартного времени отеля).
     *
     * @return array{0:Carbon,1:Carbon}
     */
    public function plannedMoments(
        Room $room,
        $startDate,
        $endDate,
        ?string $checkInTime = null,
        ?string $checkOutTime = null,
        ?Booking $existing = null
    ): array {
        $hotel = $room->hotel;
        $checkInTime = $checkInTime ?? $existing?->planned_check_in_at?->format('H:i') ?? $hotel?->standard_check_in_time ?? '14:00';
        $checkOutTime = $checkOutTime ?? $existing?->planned_check_out_at?->format('H:i') ?? $hotel?->standard_check_out_time ?? '12:00';

        return [
            Carbon::parse(Carbon::parse($startDate)->toDateString() . ' ' . $checkInTime),
            Carbon::parse(Carbon::parse($endDate)->toDateString() . ' ' . $checkOutTime),
        ];
    }

    /**
     * Активные (не удалённые) тарифы номера, пересекающие период проживания.
     *
     * @return array<int,array{id:int,title:string,date_from:string,date_to:string,price:int}>
     */
    protected function activeRates(Room $room, string $startDate, string $endDate): array
    {
        // Последняя оплачиваемая ночь — накануне выезда (минимум одна ночь).
        $lastNight = $endDate > $startDate ? NightlyPricing::addDays($endDate, -1) : $startDate;

        return $room->periodRates()
            ->active()
            ->overlapping($startDate, $lastNight)
            ->get([
                'period_rates.id',
                'period_rates.title',
                'period_rates.date_from',
                'period_rates.date_to',
                'period_rates.price',
            ])
            ->map(fn ($rate) => [
                'id' => $rate->id,
                'title' => $rate->title,
                'date_from' => $rate->date_from->toDateString(),
                'date_to' => $rate->date_to->toDateString(),
                'price' => (int) $rate->price,
            ])
            ->all();
    }

    /**
     * Доплата за ранний заезд / поздний выезд.
     * Логика: если время заезда >= стандартного check-in — доплаты нет.
     * Если время выезда <= стандартного check-out — доплаты нет.
     * Иначе ищем подходящее правило rate_rules по временному окну.
     *
     * @param int|float $nightPrice цена ночи, от которой считается доплата в процентах
     */
    protected function calcWindowCharge(?Hotel $hotel, Carbon $moment, string $type, int|float $nightPrice): float
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
            ? round($nightPrice * (float) $rule->value / 100)
            : (float) $rule->value;
    }
}
