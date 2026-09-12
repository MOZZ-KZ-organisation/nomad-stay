<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RateRule;
use Illuminate\Http\Request;

class AdminRateRuleController extends Controller
{
    /**
     * GET /admin-api/rate-rules
     * Список тарифных правил своего отеля (менеджер) или отеля из ?hotel_id= (админ).
     * ?with_trashed=1 — включить в список удалённые правила (для аудита истории цен).
     */
    public function index(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        $query = RateRule::where('hotel_id', $hotelId);
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }
        $rules = $query->orderBy('type')->orderBy('from_time')->get();

        return response()->json(['data' => $rules->map(fn ($r) => $this->format($r))]);
    }

    public function store(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        $data = $request->validate([
            'type' => 'required|in:night,half_day,early_check_in,late_check_out',
            'from_time' => 'nullable|date_format:H:i',
            'to_time' => 'nullable|date_format:H:i|after:from_time',
            'calc_type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'is_active' => 'boolean',
        ]);

        $rule = RateRule::create([...$data, 'hotel_id' => $hotelId]);

        return response()->json([
            'message' => 'Правило создано',
            'data' => $this->format($rule),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $hotelId = $this->resolveHotelId($request);
        $rule = RateRule::where('hotel_id', $hotelId)->findOrFail($id);

        $data = $request->validate([
            'type' => 'sometimes|in:night,half_day,early_check_in,late_check_out',
            'from_time' => 'nullable|date_format:H:i',
            'to_time' => 'nullable|date_format:H:i|after:from_time',
            'calc_type' => 'sometimes|in:fixed,percent',
            'value' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'is_active' => 'boolean',
        ]);

        $rule->update($data);

        return response()->json([
            'message' => 'Правило обновлено',
            'data' => $this->format($rule->fresh()),
        ]);
    }

    /**
     * Правило никогда не удаляется физически (soft delete): бронь не хранит
     * ссылку на конкретное rate_rule, только уже посчитанную сумму, поэтому
     * доказать "использовалось / не использовалось" правило нельзя — в
     * отличие от services (там есть booking_services.service_id). Soft
     * delete снимает правило из расчётов новых/пересчитываемых броней, но
     * сохраняет запись для аудита и восстановления.
     */
    public function destroy(Request $request, $id)
    {
        $hotelId = $this->resolveHotelId($request);
        $rule = RateRule::where('hotel_id', $hotelId)->findOrFail($id);
        $rule->delete(); // soft delete — см. миграцию add_soft_deletes_to_rate_rules_table

        return response()->json(['message' => 'Правило удалено из активных (запись сохранена для аудита)']);
    }

    /**
     * POST /admin-api/rate-rules/{id}/restore
     * Вернуть ранее удалённое правило обратно в расчёт.
     */
    public function restore(Request $request, $id)
    {
        $hotelId = $this->resolveHotelId($request);
        $rule = RateRule::withTrashed()->where('hotel_id', $hotelId)->findOrFail($id);

        if (!$rule->trashed()) {
            return response()->json(['message' => 'Правило и так активно'], 422);
        }
        $rule->restore();

        return response()->json([
            'message' => 'Правило восстановлено',
            'data' => $this->format($rule),
        ]);
    }

    protected function format(RateRule $r): array
    {
        return [
            'id' => $r->id,
            'type' => $r->type,
            'from_time' => $r->from_time,
            'to_time' => $r->to_time,
            'calc_type' => $r->calc_type,
            'value' => (float) $r->value,
            'currency' => $r->currency,
            'is_active' => $r->is_active,
            'deleted_at' => $r->deleted_at?->format('Y-m-d H:i'),
        ];
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