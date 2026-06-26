<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingNotification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bookings = Booking::with('user')->limit(50)->get();

        if ($bookings->count() < 50) {
            $this->command->warn('Not enough bookings to generate 50 notifications.');
            return;
        }

        $titles = [
            'Booking Confirmed',
            'Payment Pending',
            'Attendance Recorded',
            'Document Uploaded',
            'Booking Cancelled',
            'Booking Expired',
            'Beri Penilaian Lapangan',
        ];

        $messages = [
            'Booking Anda pada lapangan Futsal Arena telah disetujui.',
            'Menunggu pembayaran via QRIS untuk booking Anda.',
            'Kehadiran Anda telah tercatat. Terima kasih telah bermain!',
            'Dokumen persyaratan Anda telah diverifikasi oleh Admin.',
            'Booking Anda telah dibatalkan.',
            'Booking Anda telah kedaluwarsa.',
            'Waktu bermain Anda telah selesai. Berikan penilaian Anda sekarang!',
        ];

        foreach ($bookings as $i => $booking) {
            $user = $booking->user;
            if (!$user) continue;

            $typeIndex = $i % count($titles);
            $type = $typeIndex === 1 ? 'warning' : ($typeIndex === 5 || $typeIndex === 4 ? 'danger' : 'info');

            $user->notify(new BookingNotification(
                $titles[$typeIndex],
                $messages[$typeIndex] . ' (Booking #' . $booking->booking_number . ')',
                $type,
                $booking->id
            ));
        }

        $this->command->info('NotificationSeeder: 50 notifications successfully seeded.');
    }
}
