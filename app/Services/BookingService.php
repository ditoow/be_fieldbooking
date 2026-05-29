<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Reschedule;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function createBooking(User $user, array $scheduleIds)
    {
        if ($user->isSuspended()) {
            throw new \Exception('Akun Anda ditangguhkan sementara dari pembuatan booking baru.');
        }

        if (empty($scheduleIds)) {
            throw new \Exception('Anda harus memilih minimal satu slot jadwal.');
        }

        // Ambil semua data schedules
        $schedules = Schedule::whereIn('id', $scheduleIds)->get();

        if ($schedules->count() !== count($scheduleIds)) {
            throw new \Exception('Beberapa slot jadwal pilihan Anda tidak valid.');
        }

        foreach ($schedules as $schedule) {
            if ($schedule->status === 'booked') {
                throw new \Exception("Slot jadwal tanggal {$schedule->date} pukul {$schedule->start_time} sudah dibooking orang lain.");
            }
        }

        $isMahasiswa = $user->hasRole('mahasiswa');
        $isUmum = $user->hasRole('umum');

        if (!$isMahasiswa && !$isUmum) {
            throw new \Exception('User must have role mahasiswa or umum');
        }

        // Hitung total harga pemesanan
        $totalPrice = $schedules->sum('price');

        return DB::transaction(function () use ($user, $schedules, $isMahasiswa, $isUmum, $totalPrice) {
            $booking = Booking::create([
                'user_id' => $user->id,
                'status' => $isUmum ? 'approved' : 'pending',
                'booking_type' => $isUmum ? 'paid' : 'requirement',
                'expires_at' => $isMahasiswa ? now()->addMinutes(10) : null,
                'total_price' => $totalPrice,
            ]);

            foreach ($schedules as $schedule) {
                BookingDetail::create([
                    'booking_id' => $booking->id,
                    'schedule_id' => $schedule->id,
                ]);

                $schedule->update(['status' => 'booked']);
            }

            return $booking;
        });
    }

    public function getUserBookings(User $user, $filters = [])
    {
        $query = Booking::with(['schedules.field', 'user'])
            ->where('user_id', $user->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getBookingById($bookingId, User $user)
    {
        return Booking::with(['schedules.field', 'user'])
            ->where('id', $bookingId)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    public function uploadFile(Booking $booking, $file)
    {
        if ($booking->booking_type !== 'requirement') {
            throw new \Exception('Metode ini hanya diperuntukkan bagi pemesanan mahasiswa dengan surat persyaratan.');
        }

        if ($booking->status !== 'pending') {
            throw new \Exception('Can only upload file for pending booking');
        }

        if ($booking->expires_at && $booking->expires_at < now()) {
            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'available']);
            }
            throw new \Exception('Batas waktu mengunggah berkas persyaratan (10 menit) telah habis.');
        }

        $path = $file->store('booking-files', 'public');

        $booking->update([
            'file_url' => $path,
            'expires_at' => now()->addHours(2),
        ]);

        return $booking;
    }

    public function getAllBookings($filters = [])
    {
        $query = Booking::with(['schedules.field', 'user']);

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
            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'available']);
            }
            throw new \Exception('Booking has expired');
        }

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'approved']);

            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'booked']);
            }

            return $booking;
        });
    }

    public function rejectBooking(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            throw new \Exception('Can only reject pending booking');
        }

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'rejected']);

            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'available']);
            }

            return $booking;
        });
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
        return Booking::with(['schedules.field', 'user'])
            ->expired()
            ->get();
    }

    public function rescheduleBooking(User $user, $bookingId, $oldScheduleId, $newScheduleId)
    {
        $booking = Booking::with('schedules')->where('id', $bookingId)->where('user_id', $user->id)->firstOrFail();

        if (!$user->hasRole('mahasiswa')) {
            throw new \Exception('Fitur reschedule gratis hanya tersedia untuk mahasiswa.');
        }

        $oldSchedule = $booking->schedules->where('id', $oldScheduleId)->first();
        if (!$oldSchedule) {
            throw new \Exception('Jadwal lama tidak ditemukan dalam pemesanan ini.');
        }
        $scheduleDateTime = \Carbon\Carbon::parse($oldSchedule->date . ' ' . $oldSchedule->start_time);
        if (now()->diffInHours($scheduleDateTime, false) < 2) {
            throw new \Exception('Reschedule hanya dapat diajukan maksimal 2 jam sebelum jadwal dimulai.');
        }

        $newSchedule = Schedule::findOrFail($newScheduleId);
        if ($newSchedule->status === 'booked') {
            throw new \Exception('Jadwal baru pilihan Anda sudah dibooking.');
        }

        return DB::transaction(function () use ($booking, $oldSchedule, $newSchedule) {
            $oldSchedule->update(['status' => 'available']);

            BookingDetail::where('booking_id', $booking->id)
                ->where('schedule_id', $oldSchedule->id)
                ->update(['schedule_id' => $newSchedule->id]);

            if ($booking->status === 'approved' || $booking->status === 'pending') {
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
        $booking = Booking::with('schedules')->where('id', $bookingId)->where('user_id', $user->id)->firstOrFail();

        if (!$user->hasRole('mahasiswa')) {
            throw new \Exception('Fitur pembatalan gratis hanya tersedia untuk mahasiswa.');
        }

        foreach ($booking->schedules as $schedule) {
            $scheduleDateTime = \Carbon\Carbon::parse($schedule->date . ' ' . $schedule->start_time);
            if (now()->diffInHours($scheduleDateTime, false) < 2) {
                throw new \Exception('Pembatalan hanya dapat diajukan maksimal 2 jam sebelum jadwal dimulai.');
            }
        }

        if ($booking->status === 'cancelled') {
            throw new \Exception('Pemesanan ini sudah dibatalkan sebelumnya.');
        }

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);

            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'available']);
            }

            return $booking;
        });
    }
}