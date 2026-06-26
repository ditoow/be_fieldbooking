<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    private int $bookingCounter = 100;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mahasiswaUsers = User::role('mahasiswa')->get();
        $umumUsers = User::role('umum')->get();
        $allUsers = $mahasiswaUsers->concat($umumUsers)->shuffle();

        $todayStr = now()->toDateString();

        // Historical schedules (date < today)
        $pastSchedules = Schedule::where('date', '<', $todayStr)->get()->shuffle();
        // Future/current schedules (date >= today)
        $futureSchedules = Schedule::where('date', '>=', $todayStr)->get()->shuffle();

        // Check that we have enough schedules
        if ($pastSchedules->count() < 60 || $futureSchedules->count() < 25) {
            $this->command->warn('Not enough schedules to generate 75 bookings. Seeding default schedules first.');
            return;
        }

        $userIndex = 0;

        // 1. 50 Completed bookings (approved + attended, in the past)
        for ($i = 0; $i < 50; $i++) {
            $schedule = $pastSchedules->pop();
            $user = $allUsers[$userIndex++ % $allUsers->count()];
            
            $startParts = explode(':', $schedule->start_time);
            $attendedAt = Carbon::parse($schedule->date)
                ->setHour((int)$startParts[0])
                ->setMinute((int)$startParts[1] + 15)
                ->setSecond(0);

            $this->createBookingRecord($user, $schedule, [
                'status' => 'approved',
                'is_attended' => DB::raw('true'),
                'attended_at' => $attendedAt,
                'expires_at' => null,
                'file_url' => $user->hasRole('mahasiswa')
                    ? 'https://qcizbglhafqgrphobbly.supabase.co/storage/v1/object/public/Field-Image/booking-files/dummy_persyaratan.pdf'
                    : null,
            ]);
        }

        // 2. 10 Upcoming Approved bookings (approved + not attended, today/tomorrow)
        for ($i = 0; $i < 10; $i++) {
            $schedule = $futureSchedules->pop();
            $user = $allUsers[$userIndex++ % $allUsers->count()];

            $this->createBookingRecord($user, $schedule, [
                'status' => 'approved',
                'is_attended' => DB::raw('false'),
                'attended_at' => null,
                'expires_at' => null,
                'file_url' => null,
            ]);
        }

        // 3. 5 Pending bookings (today/tomorrow)
        for ($i = 0; $i < 5; $i++) {
            $schedule = $futureSchedules->pop();
            $user = $allUsers[$userIndex++ % $allUsers->count()];

            $this->createBookingRecord($user, $schedule, [
                'status' => 'pending',
                'is_attended' => DB::raw('false'),
                'attended_at' => null,
                'expires_at' => now()->addMinutes(10),
                'file_url' => null,
            ]);
        }

        // 4. 4 Cancelled bookings
        for ($i = 0; $i < 4; $i++) {
            $schedule = $pastSchedules->pop();
            $user = $allUsers[$userIndex++ % $allUsers->count()];

            $this->createBookingRecord($user, $schedule, [
                'status' => 'cancelled',
                'is_attended' => DB::raw('false'),
                'attended_at' => null,
                'expires_at' => null,
                'file_url' => null,
            ]);
        }

        // 5. 3 Rejected bookings
        for ($i = 0; $i < 3; $i++) {
            $schedule = $pastSchedules->pop();
            $user = $allUsers[$userIndex++ % $allUsers->count()];

            $this->createBookingRecord($user, $schedule, [
                'status' => 'rejected',
                'is_attended' => DB::raw('false'),
                'attended_at' => null,
                'expires_at' => null,
                'file_url' => null,
            ]);
        }

        // 6. 3 Expired bookings
        for ($i = 0; $i < 3; $i++) {
            $schedule = $pastSchedules->pop();
            $user = $allUsers[$userIndex++ % $allUsers->count()];

            $this->createBookingRecord($user, $schedule, [
                'status' => 'expired',
                'is_attended' => DB::raw('false'),
                'attended_at' => null,
                'expires_at' => null,
                'file_url' => null,
            ]);
        }

        $this->command->info('BookingSeeder: 75 bookings successfully seeded.');
    }

    private function createBookingRecord(User $user, Schedule $schedule, array $data): void
    {
        $bookingType = $user->hasRole('mahasiswa') ? 'requirement' : 'paid';

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
