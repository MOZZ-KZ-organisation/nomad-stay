<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingCalendarController;
use App\Models\Notification;

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    Route::get('/calendar', [BookingCalendarController::class, 'index'])
        ->name('admin.bookings.calendar');

    Route::get('/notifications', function() {
        return Notification::latest()->take(15)->get();
    });
    Route::post('/notifications/read/{id}', function($id) {
        Notification::where('id', $id)->update(['is_read' => true]);
        return response()->json(['success'=>true]);
    });
});