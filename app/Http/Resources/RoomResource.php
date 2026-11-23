<?php

namespace App\Http\Resources;

use App\Services\NightlyPricing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TCG\Voyager\Facades\Voyager;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price,
            // Цена проживания на выбранные даты с учётом тарифов на период (если даты переданы)
            ...$this->priceForDates($request),
            'beds' => $this->beds,
            'capacity' => $this->capacity,
            'bathrooms' => $this->bathrooms,
            'stock' => $this->available_stock,
            'images' => $this->images->map(fn($i) => Voyager::image($i->path)),
            'amenities' => $this->hotel
                ? $this->hotel->amenities->map(fn($a) => [
                    'code' => $a->code,
                    'name' => $a->name,
                ])->values() : [],
        ];
    }

    /**
     * Базовая цена номера (price) не зависит от дат, а тарифы на период — зависят.
     * Если в запросе есть start_date/end_date, отдаём стоимость проживания на эти даты
     * и посуточную расшифровку — теми же правилами, что и при бронировании.
     */
    protected function priceForDates(Request $request): array
    {
        if (!$request->filled('start_date') || !$request->filled('end_date')) {
            return [];
        }

        $start = substr((string) $request->input('start_date'), 0, 10);
        $end = substr((string) $request->input('end_date'), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) || $end <= $start) {
            return [];
        }

        $lastNight = NightlyPricing::addDays($end, -1);
        $rates = $this->periodRates()
            ->active()
            ->overlapping($start, $lastNight)
            ->get(['period_rates.id', 'period_rates.title', 'period_rates.date_from', 'period_rates.date_to', 'period_rates.price'])
            ->map(fn ($rate) => [
                'id' => $rate->id,
                'title' => $rate->title,
                'date_from' => $rate->date_from->toDateString(),
                'date_to' => $rate->date_to->toDateString(),
                'price' => (int) $rate->price,
            ])
            ->all();

        $pricing = NightlyPricing::resolve((int) $this->price, $start, $end, $rates);

        return [
            'nights' => $pricing['nights'],
            'price_for_period' => $pricing['total'],
            'price_breakdown' => $pricing['lines'],
        ];
    }
}
