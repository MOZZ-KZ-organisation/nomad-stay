<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingCalendarController;
use App\Http\Controllers\VoyagerSupportMessageController;
use App\Models\Notification;

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'admin', 'middleware' => ['web', 'admin.user']], function () {
    Voyager::routes();
    Route::post('/support-messages/{chat}', [VoyagerSupportMessageController::class, 'store'])
        ->name('voyager.support-messages.store');
    Route::get('/calendar', [BookingCalendarController::class, 'index'])
        ->name('admin.bookings.calendar');

    Route::get('/notifications', function() {
        return Notification::latest()->take(15)->get();
    });
    Route::post('/notifications/mark-read', function() {
        Notification::where('is_read', false)->update([
            'is_read' => true
        ]);
        return response()->json(['success'=>true]);
    });
});