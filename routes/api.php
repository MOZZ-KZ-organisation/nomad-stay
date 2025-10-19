<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SearchController;
use App\Http\Resources\ReviewResource;
use App\Models\Hotel;
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
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'toggle']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::get('user/bookings', [BookingController::class, 'userBookings']);
    Route::post('reviews', [ReviewController::class, 'store']);
});