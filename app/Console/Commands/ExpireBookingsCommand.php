<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class ExpireBookingsCommand extends Command
{
    protected $signature = 'bookings:expire';
    protected $description = 'Expire pending bookings that have passed their expires_at time';

    public function handle()
    {
        $expiredBookings = Booking::with('schedules')->where('status', 'pending')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredBookings as $booking) {
            $booking->update(['status' => 'rejected']);
            
            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'available']);
            }
            
            $this->info("Expired booking: {$booking->id}");
        }

        $this->info("Total expired bookings: {$expiredBookings->count()}");
    }
}