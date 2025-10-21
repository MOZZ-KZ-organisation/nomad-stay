<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CompleteExpiredBookings extends Command
{
    protected $signature = 'bookings:complete';
    protected $description = 'Обновляет статус бронирований, срок которых истёк';
    public function handle()
    {
        $count = Booking::where('status', 'confirmed')
            ->where('end_date', '<', Carbon::today())
            ->update(['status' => 'completed']);
        $this->info("Завершено {$count} бронирований.");
    }
}
