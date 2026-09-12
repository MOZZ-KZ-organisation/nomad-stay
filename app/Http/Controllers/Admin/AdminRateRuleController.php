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
     */
    public function index(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        $rules = RateRule::where('hotel_id', $hotelId)->orderBy('type')->orderBy('from_time')->get();

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

    public function destroy(Request $request, $id)
    {
        $hotelId = $this->resolveHotelId($request);
        $rule = RateRule::where('hotel_id', $hotelId)->findOrFail($id);
        $rule->delete();

        return response()->json(['message' => 'Правило удалено']);
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