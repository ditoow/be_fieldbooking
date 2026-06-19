<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    private int $bookingCounter = 137;

    public function run(): void
    {
        $mahasiswaUsers = User::role('mahasiswa')->get();
        $umumUsers = User::role('umum')->get();

        $mhsMap = [
            ['field_id' => 1, 'yesterday_start' => '09:00', 'today_start' => '10:00', 'today_status' => 'pending'],
            ['field_id' => 2, 'yesterday_start' => '09:00', 'today_start' => '10:00', 'today_status' => 'pending'],
            ['field_id' => 3, 'yesterday_start' => '08:00', 'today_start' => '09:00', 'today_status' => 'approved'],
            ['field_id' => 4, 'yesterday_start' => '07:00', 'today_start' => '08:00', 'today_status' => 'approved'],
        ];

        $umumMap = [
            ['field_id' => 5, 'yesterday_start' => '06:00', 'today_start' => '07:00', 'today_status' => 'pending'],
            ['field_id' => 1, 'yesterday_start' => '14:00', 'today_start' => '15:00', 'today_status' => 'approved'],
            ['field_id' => 2, 'yesterday_start' => '14:00', 'today_start' => '16:00', 'today_status' => 'pending'],
            ['field_id' => 3, 'yesterday_start' => '15:00', 'today_start' => '16:00', 'today_status' => 'approved'],
        ];

        foreach ($mahasiswaUsers as $i => $user) {
            $config = $mhsMap[$i % count($mhsMap)];
            $this->createBookingPair($user, 'requirement', $config, true);
        }

        foreach ($umumUsers as $i => $user) {
            $config = $umumMap[$i % count($umumMap)];
            $this->createBookingPair($user, 'paid', $config, false);
        }
    }

    private function createBookingPair(User $user, string $bookingType, array $config, bool $hasFileUrl): void
    {
        $scheduleYesterday = Schedule::where('field_id', $config['field_id'])
            ->where('date', now()->subDay()->toDateString())
            ->where('start_time', $config['yesterday_start'] . ':00')
            ->first();

        if ($scheduleYesterday) {
            $startParts = explode(':', $config['yesterday_start']);
            $attendedAt = now()->subDay()->setHour((int) $startParts[0])->setMinute((int) $startParts[1] + 15)->setSecond(0);

            $this->createBooking($user, $bookingType, $scheduleYesterday, [
                'status' => 'approved',
                'is_attended' => true,
                'attended_at' => $attendedAt,
                'expires_at' => null,
                'file_url' => $hasFileUrl
                    ? 'https://qcizbglhafqgrphobbly.supabase.co/storage/v1/object/public/Field-Image/booking-files/dummy_persyaratan.pdf'
                    : null,
            ]);
        }

        $scheduleToday = Schedule::where('field_id', $config['field_id'])
            ->where('date', now()->toDateString())
            ->where('start_time', $config['today_start'] . ':00')
            ->first();

        if ($scheduleToday) {
            $this->createBooking($user, $bookingType, $scheduleToday, [
                'status' => $config['today_status'],
                'is_attended' => false,
                'attended_at' => null,
                'expires_at' => $config['today_status'] === 'pending' ? now()->addMinutes(10) : null,
                'file_url' => null,
            ]);
        }
    }

    private function createBooking(User $user, string $bookingType, Schedule $schedule, array $data): void
    {
        $booking = Booking::create([
            'booking_number' => 'UGO-' . sprintf('%03d', $this->bookingCounter++) . '-' . now()->timestamp,
            'user_id' => $user->id,
            'booking_type' => $bookingType,
            'total_price' => $schedule->price,
            'status' => $data['status'],
            'is_attended' => $data['is_attended'],
            'attended_at' => $data['attended_at'],
            'expires_at' => $data['expires_at'],
            'file_url' => $data['file_url'],
        ]);

        BookingDetail::create([
            'booking_id' => $booking->id,
            'schedule_id' => $schedule->id,
        ]);
    }
}
