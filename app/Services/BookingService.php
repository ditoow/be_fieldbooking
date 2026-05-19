<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function createBooking(User $user, $scheduleId)
    {
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
                'status' => 'pending',
                'booking_type' => $isUmum ? 'paid' : 'requirement',
                'expires_at' => $isMahasiswa ? now()->addHours(2) : now()->addMinutes(10),
            ]);

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
}