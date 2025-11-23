<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingCalendarController;

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    Route::get('/calendar', [BookingCalendarController::class, 'index'])
        ->name('admin.bookings.calendar');
});