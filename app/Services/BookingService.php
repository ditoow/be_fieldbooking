<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Reschedule;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function createBooking(User $user, $scheduleId)
    {
        if ($user->isSuspended()) {
            throw new \Exception('Akun Anda ditangguhkan sementara dari pembuatan booking baru.');
        }

        $schedule = Schedule::findOrFail($scheduleId);

        if ($schedule->status === 'booked') {
            throw new \Exception('Schedule already booked');
        }

        $isMahasiswa = $user->hasRole('mahasiswa');
        $isUmum = $user->hasRole('umum');

        if (!$isMahasiswa && !$isUmum) {
            throw new \Exception('User must have role mahasiswa or umum');
        }

        return DB::transaction(function () use ($user, $schedule, $isMahasiswa, $isUmum) {
            $booking = Booking::create([
                'user_id' => $user->id,
                'schedule_id' => $schedule->id,
                'status' => $isUmum ? 'approved' : 'pending',
                'booking_type' => $isUmum ? 'paid' : 'requirement',
                'expires_at' => $isMahasiswa ? now()->addHours(2) : null,
            ]);

            if ($isUmum) {
                $schedule->update(['status' => 'booked']);
            }

            return $booking;
        });
    }

    public function getUserBookings(User $user, $filters = [])
    {
        $query = Booking::with(['schedule.field', 'user'])
            ->where('user_id', $user->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getBookingById($bookingId, User $user)
    {
        return Booking::with(['schedule.field', 'user'])
            ->where('id', $bookingId)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    public function uploadFile(Booking $booking, $file)
    {
        if ($booking->booking_type !== 'requirement') {
            throw new \Exception('This booking type does not require file upload');
        }

        if ($booking->status !== 'pending') {
            throw new \Exception('Can only upload file for pending booking');
        }

        $path = $file->store('booking-files', 'public');

        $booking->update(['file_url' => $path]);

        return $booking;
    }

    public function getAllBookings($filters = [])
    {
        $query = Booking::with(['schedule.field', 'user']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['booking_type'])) {
            $query->where('booking_type', $filters['booking_type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function approveBooking(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            throw new \Exception('Can only approve pending booking');
        }

        if ($booking->expires_at && $booking->expires_at < now()) {
            $booking->update(['status' => 'rejected']);
            throw new \Exception('Booking has expired');
        }

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'approved']);

            if ($booking->schedule) {
                $booking->schedule->update(['status' => 'booked']);
            }

            return $booking;
        });
    }

    public function rejectBooking(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            throw new \Exception('Can only reject pending booking');
        }

        $booking->update(['status' => 'rejected']);

        return $booking;
    }

    public function markAttendance(Booking $booking)
    {
        if ($booking->status !== 'approved') {
            throw new \Exception('Can only mark attendance for approved booking');
        }

        $booking->update([
            'is_attended' => true,
            'attended_at' => now(),
        ]);

        return $booking;
    }

    public function getExpiredBookings()
    {
        return Booking::with(['schedule.field', 'user'])
            ->expired()
            ->get();
    }

    public function rescheduleBooking(User $user, $bookingId, $newScheduleId)
    {
        $booking = Booking::with('schedule')->where('id', $bookingId)->where('user_id', $user->id)->firstOrFail();

        if (!$user->hasRole('mahasiswa')) {
            throw new \Exception('Fitur reschedule gratis hanya tersedia untuk mahasiswa.');
        }

        // Validasi batas waktu
        $scheduleDateTime = \Carbon\Carbon::parse($booking->schedule->date . ' ' . $booking->schedule->start_time);
        if (now()->diffInHours($scheduleDateTime, false) < 2) {
            throw new \Exception('Reschedule hanya dapat diajukan maksimal 2 jam sebelum jadwal dimulai.');
        }

        $newSchedule = Schedule::findOrFail($newScheduleId);
        if ($newSchedule->status === 'booked') {
            throw new \Exception('Jadwal baru pilihan Anda sudah dibooking.');
        }

        return DB::transaction(function () use ($booking, $newSchedule) {
            $oldSchedule = $booking->schedule;

            
            $oldSchedule->update(['status' => 'available']);

            
            $booking->update([
                'schedule_id' => $newSchedule->id
            ]);

            if ($booking->status === 'approved') {
                $newSchedule->update(['status' => 'booked']);
            }
            Reschedule::create([
                'booking_id' => $booking->id,
                'old_schedule_id' => $oldSchedule->id,
                'new_schedule_id' => $newSchedule->id,
            ]);

            return $booking;
        });
    }

    public function cancelBooking(User $user, $bookingId)
    {
        $booking = Booking::with('schedule')->where('id', $bookingId)->where('user_id', $user->id)->firstOrFail();

        if (!$user->hasRole('mahasiswa')) {
            throw new \Exception('Fitur pembatalan gratis hanya tersedia untuk mahasiswa.');
        }

        
        $scheduleDateTime = \Carbon\Carbon::parse($booking->schedule->date . ' ' . $booking->schedule->start_time);
        if (now()->diffInHours($scheduleDateTime, false) < 2) {
            throw new \Exception('Pembatalan hanya dapat diajukan maksimal 2 jam sebelum jadwal dimulai.');
        }

        if ($booking->status === 'cancelled') {
            throw new \Exception('Pemesanan ini sudah dibatalkan sebelumnya.');
        }

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);

            if ($booking->schedule) {
                $booking->schedule->update(['status' => 'available']);
            }

            return $booking;
        });
    }
}