<?php

use App\Http\Controllers\AmenityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingChatController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingMessageController;
use App\Http\Controllers\BookingPriceController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\SupportMessageController;
use App\Http\Resources\ReviewResource;
use App\Models\Hotel;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'sendCode']);
Route::post('/verify-otp', [AuthController::class, 'verifyCode']);

Route::get('/hotels/recent', [HotelController::class, 'recent']);

Route::get('/amenities', [AmenityController::class, 'index']);
Route::get('/cities', [CityController::class, 'index']);
Route::get('rooms/{room}', [RoomController::class, 'show']);
Route::get('hotels/{hotel}/reviews', function (Hotel $hotel) {
    return ReviewResource::collection($hotel->reviews()->latest()->paginate(10));
});
Route::get('search', [SearchController::class, 'index']);
Route::get('hotels/{hotel}', [HotelController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'toggle']);
    Route::get('/bookings/price-details', [BookingPriceController::class, 'show']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::get('user/bookings', [BookingController::class, 'userBookings']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::patch('/bookings/{booking}/dates', [BookingController::class, 'updateDates']);
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('reviews', [ReviewController::class, 'store']);

    Route::get('/booking-chats', [BookingChatController::class, 'index']);
    Route::post('/booking-chats/{booking}', [BookingChatController::class, 'create']);
    Route::get('/booking-chats/{chat}/messages', [BookingMessageController::class, 'index']);
    Route::post('/booking-chats/{chat}/messages', [BookingMessageController::class, 'store']);
    Route::get('/support-chats', [SupportChatController::class, 'index']);
    Route::get('/support-chat', [SupportChatController::class, 'show']);
    Route::post('/support-chat/messages', [SupportMessageController::class, 'store']);
});