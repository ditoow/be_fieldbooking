<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $mahasiswa = User::where('email', 'mhs@mhs.dinus.ac.id')->first();
        $umum = User::where('email', 'umum@umum.com')->first();

        if (!$mahasiswa || !$umum) {
            return;
        }

        $scheduleMhs1 = Schedule::where('field_id', 1)
            ->where('date', now()->addDay()->toDateString())
            ->where('start_time', '09:00:00')
            ->first();

        if ($scheduleMhs1) {
            $booking1 = Booking::create([
                'user_id' => $mahasiswa->id,
                'status' => 'approved',
                'booking_type' => 'requirement',
                'total_price' => $scheduleMhs1->price,
                'file_url' => 'https://res.cloudinary.com/dawn0omj0/image/upload/v1780116902/dummy_persyaratan.pdf', 
                'is_attended' => true,
                'attended_at' => now()->addDay()->setHour(9)->setMinute(15), 
                'expires_at' => null,
            ]);

            BookingDetail::create([
                'booking_id' => $booking1->id,
                'schedule_id' => $scheduleMhs1->id,
            ]);

            $scheduleMhs1->update(['status' => 'booked']);
        }

        $scheduleMhs2 = Schedule::where('field_id', 1)
            ->where('date', now()->addDay()->toDateString())
            ->where('start_time', '15:00:00')
            ->first();

        if ($scheduleMhs2) {
            $booking2 = Booking::create([
                'user_id' => $mahasiswa->id,
                'status' => 'pending',
                'booking_type' => 'requirement',
                'total_price' => $scheduleMhs2->price,
                'file_url' => null, 
                'is_attended' => false,
                'attended_at' => null,
                'expires_at' => now()->addHours(2), 
            ]);

            BookingDetail::create([
                'booking_id' => $booking2->id,
                'schedule_id' => $scheduleMhs2->id,
            ]);
        }

        $scheduleUmum1 = Schedule::where('field_id', 2)
            ->where('date', now()->addDay()->toDateString())
            ->where('start_time', '19:00:00')
            ->first();

        if ($scheduleUmum1) {
            $booking3 = Booking::create([
                'user_id' => $umum->id,
                'status' => 'approved',
                'booking_type' => 'paid',
                'total_price' => $scheduleUmum1->price,
                'file_url' => null, 
                'is_attended' => false,
                'attended_at' => null,
                'expires_at' => null,
            ]);

            BookingDetail::create([
                'booking_id' => $booking3->id,
                'schedule_id' => $scheduleUmum1->id,
            ]);

            $scheduleUmum1->update(['status' => 'booked']);
        }
    }
}
