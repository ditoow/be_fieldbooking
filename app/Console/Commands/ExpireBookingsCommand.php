<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\MidtransService;
use Illuminate\Console\Command;

class ExpireBookingsCommand extends Command
{
    protected $signature = 'bookings:expire';
    protected $description = 'Expire pending bookings that have passed their expires_at time';

    public function handle(MidtransService $midtransService)
    {
        $expiredBookings = Booking::with('schedules')->where('status', 'pending')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredBookings as $booking) {
            if ($booking->booking_type === 'paid' && $booking->qr_id) {
                $midtransService->cancelTransaction($booking->qr_id);
            }

            $booking->transitionTo('rejected');

            $this->info("Expired booking: {$booking->id}");
        }

        $this->info("Total expired bookings: {$expiredBookings->count()}");
    }
}