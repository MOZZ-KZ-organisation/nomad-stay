<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request, Hotel $hotel)
    {
        $user = $request->user();
        if ($user->favorites()->where('hotel_id', $hotel->id)->exists()) {
            $user->favorites()->detach($hotel->id);
            return response()->json(['message' => 'Removed from favorites']);
        }
        $user->favorites()->attach($hotel->id);
        return response()->json(['message' => 'Added to favorites']);
    }

    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()
            ->with(['images' => fn($q) => $q->where('is_main', true)])
            ->get(['id', 'title', 'slug', 'city_id', 'stars', 'min_price']);
        return response()->json($favorites);
    }
}
