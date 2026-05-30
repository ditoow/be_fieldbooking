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
        $mahasiswa = User::where('email', 'mhs@mhs.dinus.ac.id')->first();
        $umum = User::where('email', 'umum@umum.com')->first();

        if (!$mahasiswa || !$umum) {
            return;
        }

        $mahasiswaBookings = Booking::where('user_id', $mahasiswa->id)->get();
        $umumBookings = Booking::where('user_id', $umum->id)->get();

        // --- Notifications for Mahasiswa ---

        // Approved booking notification
        if ($approvedBooking = $mahasiswaBookings->where('status', 'approved')->first()) {
            $mahasiswa->notify(new BookingNotification(
                'Booking Approved',
                'Your booking ' . $approvedBooking->booking_number . ' has been approved by the admin. Please come on time.',
                'success',
                $approvedBooking->id,
            ));
        }

        // Pending booking notification
        if ($pendingBooking = $mahasiswaBookings->where('status', 'pending')->first()) {
            $mahasiswa->notify(new BookingNotification(
                'Booking Submitted',
                'Your booking ' . $pendingBooking->booking_number . ' has been submitted. Please wait for admin approval.',
                'info',
                $pendingBooking->id,
            ));
        }

        // Attendance confirmation
        if ($approvedBooking) {
            $mahasiswa->notify(new BookingNotification(
                'Attendance Confirmed',
                'Your attendance for booking ' . $approvedBooking->booking_number . ' has been recorded. Thank you!',
                'success',
                $approvedBooking->id,
            ));
        }

        // General reminder
        $mahasiswa->notify(new BookingNotification(
            'Reminder',
            'Please upload your requirement document before the booking expires.',
            'warning',
        ));

        // --- Notifications for Umum ---

        // Approved booking notification
        if ($umumApproved = $umumBookings->where('status', 'approved')->first()) {
            $umum->notify(new BookingNotification(
                'Booking Approved',
                'Your booking ' . $umumApproved->booking_number . ' has been approved. See you at the field!',
                'success',
                $umumApproved->id,
            ));
        }

        // Welcome notification
        $umum->notify(new BookingNotification(
            'Welcome',
            'Welcome to Field Booking! Browse available fields and book your favorite one.',
            'info',
        ));

        // Mark older notifications as read
        $mahasiswa->notifications()->orderBy('created_at', 'asc')->limit(2)->get()->each->markAsRead();
        $umum->notifications()->orderBy('created_at', 'asc')->limit(1)->get()->each->markAsRead();
    }
}
