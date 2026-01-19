<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class AutoCheckoutBookings extends Command
{
    protected $signature = 'app:auto-checkout-bookings';
    protected $description = 'Command description';

    public function handle()
    {
        Booking::where('status', 'checked_in')
            ->whereDate('end_date', '<=', now()->toDateString())
            ->update(['status' => 'checked_out']);
    }
}
