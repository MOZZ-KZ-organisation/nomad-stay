<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * GET /api/services?hotel_id=1
     * Публичный справочник доп. услуг отеля для гостя (только активные —
     * выбор при создании брони).
     */
    public function index(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|integer|exists:hotels,id',
        ]);

        $services = Service::where('hotel_id', $request->integer('hotel_id'))
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'category', 'price', 'unit']);

        return response()->json([
            'data' => $services,
        ]);
    }
}