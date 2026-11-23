<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodRate;
use App\Models\Room;
use App\Services\NightlyPricing;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Тарифы на период по датам: «с 1 декабря по 15 января номер стоит 25 000 за ночь».
 * Один тариф назначается сразу на несколько номеров отеля.
 *
 * Правила:
 *  - date_from / date_to включительно, по датам ночей (ночь = дата вечера заезда);
 *  - у одного номера активные тарифы не могут пересекаться по датам — иначе цена ночи
 *    была бы неоднозначной. При пересечении API отвечает 422 со списком конфликтов;
 *  - тариф влияет только на новые и пересчитываемые брони. Уже созданные брони хранят
 *    снимок посуточной расшифровки (bookings.price_breakdown), поэтому правка, удаление
 *    или восстановление тарифа их цену не меняет.
 */
class AdminPeriodRateController extends Controller
{
    /**
     * GET /admin-api/period-rates
     * Тарифы своего отеля (менеджер) или отеля из ?hotel_id= (админ).
     *
     * Фильтры: ?room_id=, ?is_active=, ?date_from=&date_to= (пересечение с периодом),
     * ?with_trashed=1 — вместе с удалёнными, ?only_trashed=1 — только удалённые (корзина).
     */
    public function index(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);

        $request->validate([
            'room_id' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = PeriodRate::where('hotel_id', $hotelId)->with('rooms:id,title');

        if ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        } elseif ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        if ($request->filled('room_id')) {
            $query->whereHas('rooms', fn ($q) => $q->where('rooms.id', $request->room_id));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->overlapping(
                $request->filled('date_from') ? Carbon::parse($request->date_from)->toDateString() : '0001-01-01',
                $request->filled('date_to') ? Carbon::parse($request->date_to)->toDateString() : '9999-12-31'
            );
        }

        $rates = $query->orderBy('date_from')->orderBy('id')->get();

        return response()->json(['data' => $rates->map(fn ($r) => $this->format($r))]);
    }

    /**
     * GET /admin-api/period-rates/{id}
     */
    public function show(Request $request, $id)
    {
        $hotelId = $this->resolveHotelId($request);
        $rate = PeriodRate::withTrashed()->with('rooms:id,title')->where('hotel_id', $hotelId)->findOrFail($id);

        return response()->json(['data' => $this->format($rate)]);
    }

    /**
     * POST /admin-api/period-rates
     */
    public function store(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'price' => 'required|integer|min:0',
            'room_ids' => 'required|array|min:1',
            'room_ids.*' => 'integer|distinct',
            'is_active' => 'boolean',
        ]);

        $dateFrom = Carbon::parse($data['date_from'])->toDateString();
        $dateTo = Carbon::parse($data['date_to'])->toDateString();
        $roomIds = $this->resolveRoomIds($hotelId, $data['room_ids']);
        $isActive = $data['is_active'] ?? true;

        if ($isActive) {
            $this->assertNoConflicts($hotelId, $roomIds, $dateFrom, $dateTo);
        }

        $rate = DB::transaction(function () use ($data, $hotelId, $dateFrom, $dateTo, $roomIds, $isActive) {
            $rate = PeriodRate::create([
                'hotel_id' => $hotelId,
                'title' => $data['title'],
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'price' => $data['price'],
                'is_active' => $isActive,
            ]);
            $rate->rooms()->sync($roomIds);

            return $rate;
        });

        return response()->json([
            'message' => 'Тариф создан',
            'data' => $this->format($rate->load('rooms:id,title')),
        ], 201);
    }

    /**
     * PATCH /admin-api/period-rates/{id}
     * Можно менять название, даты, цену, активность и набор номеров (room_ids заменяет набор целиком).
     * Цена уже созданных броней не меняется.
     */
    public function update(Request $request, $id)
    {
        $hotelId = $this->resolveHotelId($request);
        $rate = PeriodRate::where('hotel_id', $hotelId)->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'price' => 'sometimes|integer|min:0',
            'room_ids' => 'sometimes|array|min:1',
            'room_ids.*' => 'integer|distinct',
            'is_active' => 'sometimes|boolean',
        ]);

        $dateFrom = isset($data['date_from']) ? Carbon::parse($data['date_from'])->toDateString() : $rate->date_from->toDateString();
        $dateTo = isset($data['date_to']) ? Carbon::parse($data['date_to'])->toDateString() : $rate->date_to->toDateString();
        if ($dateTo < $dateFrom) {
            throw ValidationException::withMessages([
                'date_to' => ['Дата окончания не может быть раньше даты начала'],
            ]);
        }

        $roomIds = isset($data['room_ids'])
            ? $this->resolveRoomIds($hotelId, $data['room_ids'])
            : $rate->rooms()->pluck('rooms.id')->all();
        $isActive = $data['is_active'] ?? $rate->is_active;

        if ($isActive) {
            $this->assertNoConflicts($hotelId, $roomIds, $dateFrom, $dateTo, $rate->id);
        }

        DB::transaction(function () use ($rate, $data, $dateFrom, $dateTo, $isActive, $roomIds) {
            $rate->update([
                'title' => $data['title'] ?? $rate->title,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'price' => $data['price'] ?? $rate->price,
                'is_active' => $isActive,
            ]);
            if (isset($data['room_ids'])) {
                $rate->rooms()->sync($roomIds);
            }
        });

        return response()->json([
            'message' => 'Тариф обновлён',
            'data' => $this->format($rate->fresh('rooms:id,title')),
        ]);
    }

    /**
     * DELETE /admin-api/period-rates/{id}
     * Soft delete: тариф перестаёт участвовать в расчёте новых броней, но запись и
     * привязка к номерам сохраняются — тариф можно восстановить.
     * Созданные брони хранят свой снимок цены, поэтому удаление их не затрагивает.
     */
    public function destroy(Request $request, $id)
    {
        $hotelId = $this->resolveHotelId($request);
        $rate = PeriodRate::where('hotel_id', $hotelId)->findOrFail($id);
        $rate->delete();

        return response()->json(['message' => 'Тариф удалён (его можно восстановить)']);
    }

    /**
     * POST /admin-api/period-rates/{id}/restore
     * Вернуть удалённый тариф. Если за это время на те же номера и даты появился
     * другой активный тариф — 422 со списком конфликтов.
     */
    public function restore(Request $request, $id)
    {
        $hotelId = $this->resolveHotelId($request);
        $rate = PeriodRate::withTrashed()->where('hotel_id', $hotelId)->findOrFail($id);

        if (!$rate->trashed()) {
            return response()->json(['message' => 'Тариф и так активен'], 422);
        }

        if ($rate->is_active) {
            $this->assertNoConflicts(
                $hotelId,
                $rate->rooms()->pluck('rooms.id')->all(),
                $rate->date_from->toDateString(),
                $rate->date_to->toDateString(),
                $rate->id
            );
        }

        $rate->restore();

        return response()->json([
            'message' => 'Тариф восстановлен',
            'data' => $this->format($rate->load('rooms:id,title')),
        ]);
    }

    protected function format(PeriodRate $r): array
    {
        $rooms = $r->relationLoaded('rooms') ? $r->rooms : collect();

        return [
            'id' => $r->id,
            'title' => $r->title,
            'date_from' => $r->date_from->format('Y-m-d'),
            'date_to' => $r->date_to->format('Y-m-d'),
            // Сколько ночей покрывает тариф (обе границы включительно).
            'nights' => NightlyPricing::daysBetween($r->date_from->toDateString(), $r->date_to->toDateString()) + 1,
            'price' => (int) $r->price,
            'currency' => 'KZT',
            'is_active' => $r->is_active,
            'room_ids' => $rooms->pluck('id')->values(),
            'rooms' => $rooms->map(fn ($room) => ['id' => $room->id, 'title' => $room->title])->values(),
            'deleted_at' => $r->deleted_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Все номера должны принадлежать отелю — нельзя назначить тариф на чужой номер.
     *
     * @return array<int,int>
     */
    protected function resolveRoomIds(int $hotelId, array $roomIds): array
    {
        $roomIds = array_values(array_unique(array_map('intval', $roomIds)));
        $found = Room::where('hotel_id', $hotelId)->whereIn('id', $roomIds)->pluck('id')->all();

        $missing = array_values(array_diff($roomIds, $found));
        if ($missing) {
            throw ValidationException::withMessages([
                'room_ids' => ['Номера не найдены в этом отеле: ' . implode(', ', $missing)],
            ]);
        }

        return $roomIds;
    }

    /**
     * Активные тарифы тех же номеров, пересекающиеся по датам, — 422 с описанием конфликтов.
     */
    protected function assertNoConflicts(int $hotelId, array $roomIds, string $dateFrom, string $dateTo, ?int $ignoreId = null): void
    {
        $conflicts = PeriodRate::where('hotel_id', $hotelId)
            ->active()
            ->overlapping($dateFrom, $dateTo)
            ->when($ignoreId, fn ($q) => $q->where('period_rates.id', '!=', $ignoreId))
            ->whereHas('rooms', fn ($q) => $q->whereIn('rooms.id', $roomIds))
            ->with('rooms:id,title')
            ->get();

        if ($conflicts->isEmpty()) {
            return;
        }

        $payload = $conflicts->map(function (PeriodRate $c) use ($roomIds) {
            $shared = $c->rooms->whereIn('id', $roomIds);

            return [
                'id' => $c->id,
                'title' => $c->title,
                'date_from' => $c->date_from->format('Y-m-d'),
                'date_to' => $c->date_to->format('Y-m-d'),
                'rooms' => $shared->map(fn ($room) => ['id' => $room->id, 'title' => $room->title])->values(),
            ];
        })->values();

        throw new HttpResponseException(response()->json([
            'message' => 'Тариф пересекается по датам с другим тарифом на тех же номерах: '
                . $this->describeConflicts($payload),
            'errors' => ['date_from' => ['Период пересекается с другим активным тарифом']],
            'conflicts' => $payload,
        ], 422));
    }

    protected function describeConflicts(Collection $conflicts): string
    {
        return $conflicts->map(fn ($c) => sprintf(
            '«%s» (%s — %s; номера: %s)',
            $c['title'],
            $c['date_from'],
            $c['date_to'],
            collect($c['rooms'])->pluck('title')->implode(', ')
        ))->implode('; ');
    }

    /**
     * Менеджер работает только со своим отелем, админ может передать ?hotel_id=.
     */
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
