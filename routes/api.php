<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SearchController;
use App\Http\Resources\ReviewResource;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/cities', [CityController::class, 'index']);
Route::get('search', [SearchController::class, 'index']);
Route::get('hotels/{hotel}', [HotelController::class, 'show']);
Route::get('rooms/{room}', [RoomController::class, 'show']);

// Public reviews listing
Route::get('hotels/{hotel}/reviews', function (Hotel $hotel) {
    return ReviewResource::collection($hotel->reviews()->latest()->paginate(10));
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('bookings', [BookingController::class, 'store']);
    Route::get('user/bookings', [BookingController::class, 'userBookings']);
    Route::post('reviews', [ReviewController::class, 'store']);

    // favorites (simple)
    Route::post('favorites/{hotel}', function (Hotel $hotel, Request $request) {
        Favorite::firstOrCreate(['user_id' => $request->user()->id, 'hotel_id' => $hotel->id]);
        return response()->json(['ok' => true]);
    });
    Route::delete('favorites/{hotel}', function (Hotel $hotel, Request $request) {
        Favorite::where('user_id', $request->user()->id)->where('hotel_id', $hotel->id)->delete();
        return response()->noContent();
    });
});