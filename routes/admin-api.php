<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminContentController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminGuestController;
use App\Http\Controllers\Admin\AdminHotelController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminRoomController;
use App\Http\Controllers\Admin\AdminSupportController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AdminAuthController::class, 'login']);
Route::post('/register', [AdminAuthController::class, 'register']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/me', [AdminAuthController::class, 'me']);
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/notifications', [AdminDashboardController::class, 'notifications']);
    Route::patch('/notifications/read-all', [AdminDashboardController::class, 'markAllRead']);
    Route::patch('/notifications/{id}/read', [AdminDashboardController::class, 'markRead']);
    Route::get('/bookings/calendar', [AdminBookingController::class, 'calendar']);
    Route::apiResource('/bookings', AdminBookingController::class);
    Route::get('/my-hotel', [AdminHotelController::class, 'show']);
    Route::patch('/my-hotel', [AdminHotelController::class, 'update']);
    Route::post('/my-hotel/images', [AdminHotelController::class, 'uploadImages']);
    Route::delete('/my-hotel/images/{imageId}', [AdminHotelController::class, 'deleteImage']);
    Route::patch('/my-hotel/nearby', [AdminHotelController::class, 'updateNearby']);
    Route::patch('/my-hotel/discount', [AdminHotelController::class, 'updateDiscount']);
    Route::apiResource('/rooms', AdminRoomController::class);
    Route::post('/rooms/{id}/images', [AdminRoomController::class, 'uploadImages']);
    Route::delete('/rooms/{roomId}/images/{imageId}', [AdminRoomController::class, 'deleteImage']);
    Route::prefix('/reports')->group(function () {
        Route::get('/revenue', [AdminReportController::class, 'revenue']);
        Route::get('/occupancy', [AdminReportController::class, 'occupancy']);
        Route::get('/bookings-by-source', [AdminReportController::class, 'bySource']);
        Route::get('/reviews', [AdminReportController::class, 'reviews']);
    });
    Route::get('/guests', [AdminGuestController::class, 'index']);
    Route::get('/guests/{id}', [AdminGuestController::class, 'show']);
    Route::patch('/guests/{id}/block', [AdminGuestController::class, 'toggleBlock']);
    Route::get('/reviews', [AdminReviewController::class, 'index']);
    Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);
    Route::get('/support-chats', [AdminSupportController::class, 'index']);
    Route::get('/support-chats/{id}', [AdminSupportController::class, 'show']);
    Route::post('/support-chats/{id}/messages', [AdminSupportController::class, 'reply']);
    Route::get('/booking-chats', [AdminSupportController::class, 'bookingChats']);
    Route::get('/booking-chats/{id}', [AdminSupportController::class, 'showBookingChat']);
    Route::post('/booking-chats/{id}/messages', [AdminSupportController::class, 'replyBookingChat']);
    // Route::get('/cities', [AdminContentController::class, 'cities']);
    // Route::post('/cities', [AdminContentController::class, 'storeCity']);
    // Route::patch('/cities/{id}', [AdminContentController::class, 'updateCity']);
    // Route::delete('/cities/{id}', [AdminContentController::class, 'destroyCity']);
    // Route::get('/countries', [AdminContentController::class, 'countries']);
    // Route::post('/countries', [AdminContentController::class, 'storeCountry']);
    // Route::patch('/countries/{id}', [AdminContentController::class, 'updateCountry']);
    // Route::delete('/countries/{id}', [AdminContentController::class, 'destroyCountry']);
    // Route::get('/amenities', [AdminContentController::class, 'amenities']);
    // Route::post('/amenities', [AdminContentController::class, 'storeAmenity']);
    // Route::patch('/amenities/{id}', [AdminContentController::class, 'updateAmenity']);
    // Route::delete('/amenities/{id}', [AdminContentController::class, 'destroyAmenity']);
    // Route::get('/city-attractions', [AdminContentController::class, 'attractions']);
    // Route::post('/city-attractions', [AdminContentController::class, 'storeAttraction']);
    // Route::delete('/city-attractions/{id}', [AdminContentController::class, 'destroyAttraction']);
});