<?php

use App\Http\Controllers\AdminBookingChatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingCalendarController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ManagerAuthController;
use App\Http\Controllers\VoyagerSupportMessageController;
use App\Models\Notification;

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    Route::group(['middleware' => 'admin.user'], function () {
        Route::get('/calendar', [BookingCalendarController::class, 'index'])
            ->name('admin.bookings.calendar');
        Route::get('/notifications', function () {
            return Notification::latest()->take(15)->get();
        });
        Route::post('/notifications/mark-read', function () {
            Notification::where('is_read', false)->update([
                'is_read' => true
            ]);
            return response()->json(['success' => true]);
        });
        Route::post('/bookings/{id}/quick-update', [BookingController::class, 'quickUpdate'])
            ->name('bookings.quick-update');
        Route::get('/booking-chats', [AdminBookingChatController::class, 'index'])
            ->name('admin.booking-chats.index');
        Route::get('/booking-chats/{chat}', [AdminBookingChatController::class, 'show'])
            ->name('admin.booking-chats.show');
        Route::post('/booking-chats/{chat}/reply', [AdminBookingChatController::class, 'reply'])
            ->name('admin.booking-chats.reply');

    });
});

Route::prefix('manager')->name('manager.')->group(function () {
    Route::get('/register',  [ManagerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [ManagerAuthController::class, 'register']);
});