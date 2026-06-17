<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Field;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Reschedule;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use App\Models\Media;
use Illuminate\Support\Facades\Log;

class BookingService
{
    protected SupabaseService $supabaseService;
    protected MidtransService $midtransService;
    protected ScheduleService $scheduleService;

    public function __construct(
        SupabaseService $supabaseService,
        MidtransService $midtransService,
        ScheduleService $scheduleService
    ) {
        $this->supabaseService = $supabaseService;
        $this->midtransService = $midtransService;
        $this->scheduleService = $scheduleService;
    }

    public function createBooking(User $user, int $fieldId, string $date, array $timeSlots)
    {
        $this->cleanupExpiredBookings();

        if ($user->isSuspended()) {
            throw new \Exception('Your account is temporarily suspended from creating new bookings.');
        }

        if (empty($timeSlots)) {
            throw new \Exception('You must select at least one time slot.');
        }

        // Validasi field ada
        $field = Field::findOrFail($fieldId);

        $isMahasiswa = $user->hasRole('mahasiswa');
        $isUmum = $user->hasRole('umum');

        if (!$isMahasiswa && !$isUmum) {
            throw new \Exception('User must have role mahasiswa or umum');
        }

        if ($isMahasiswa && count($timeSlots) > 3) {
            throw new \Exception('Students can only book a maximum of 3 hours per booking.');
        }

        // Hitung total harga
        $totalPrice = 0;
        foreach ($timeSlots as $startTime) {
            $hour = (int) substr($startTime, 0, 2);
            $totalPrice += ($hour >= 16) ? config('pricing.after_16') : config('pricing.before_16');
        }

        $booking = DB::transaction(function () use ($user, $fieldId, $date, $timeSlots, $isMahasiswa, $isUmum, $totalPrice) {
            $booking = Booking::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'booking_type' => $isUmum ? 'paid' : 'requirement',
                'expires_at' => $isMahasiswa ? now()->addMinutes(10) : now()->addMinutes(10),
                'total_price' => $totalPrice,
            ]);

            foreach ($timeSlots as $startTime) {
                // Buat schedule record on-demand
                $schedule = $this->scheduleService->createScheduleForBooking($fieldId, $date, $startTime);

                BookingDetail::create([
                    'booking_id' => $booking->id,
                    'schedule_id' => $schedule->id,
                ]);
            }

            return $booking;
        });

        if ($isUmum) {
            try {
                if ($booking->booking_type === 'paid') {
                    $midtransResult = $this->midtransService->createQris($booking);
                    $booking->update([
                        'qr_id' => $midtransResult['transaction_id'],
                        'qr_string' => $midtransResult['qr_string'] ?? ($midtransResult['actions'][0]->url ?? null),
                    ]);
                }
            } catch (\Exception $e) {
                DB::transaction(function () use ($booking) {
                    $booking->transitionTo('rejected');
                });
                throw $e;
            }

            $booking->load('schedules.field');
            $firstSchedule = $booking->schedules->sortBy('start_time')->first();
            $lastSchedule = $booking->schedules->sortBy('start_time')->last();
            $fieldName = $firstSchedule->field->name ?? 'Field';
            $startTime = $firstSchedule ? date('H:i', strtotime($firstSchedule->start_time)) : '';
            $endTime = $lastSchedule ? date('H:i', strtotime($lastSchedule->end_time)) : '';

            $user->notify(new \App\Notifications\BookingNotification(
                'Waiting for Payment',
                "Booking {$fieldName} at {$startTime} - {$endTime} has been created. Please complete your payment.",
                'info',
                $booking->id
            ));
        }

        ActivityLog::create([
            'type' => 'info',
            'title' => 'New Booking',
            'description' => "Booking {$booking->booking_number} was created by {$user->name} for {$date}.",
            'user_name' => $user->name,
            'user_id' => $user->id,
        ]);

        return $booking;
    }

    public function getUserBookings(User $user, $filters = [], $perPage = 10)
    {
        $this->cleanupExpiredBookings();

        $query = Booking::with(['schedules.field', 'user', 'rating'])
            ->where('user_id', $user->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getBookingById(int $bookingId, User $user)
    {
        $this->cleanupExpiredBookings();

        $booking = Booking::with(['schedules.field', 'user', 'rating'])
            ->where('id', $bookingId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($booking->status === 'pending' && $booking->booking_type === 'paid' && $booking->qr_id) {
            $midtransStatus = $this->midtransService->checkTransactionStatus($booking->qr_id);
            if ($midtransStatus) {
                $transactionStatus = $midtransStatus->transaction_status ?? null;
                $fraudStatus = $midtransStatus->fraud_status ?? null;

                if ($transactionStatus == 'capture') {
                    if ($fraudStatus == 'accept') {
                        $booking = $this->approvePaidBooking($booking);
                    }
                } else if ($transactionStatus == 'settlement') {
                    $booking = $this->approvePaidBooking($booking);
                } else if (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                    $booking = $this->failPaidBooking($booking, $transactionStatus);
                }
            }
        }

        return $booking;
    }

    public function uploadFile(Booking $booking, \Illuminate\Http\UploadedFile $file)
    {
        if ($booking->booking_type !== 'requirement') {
            throw new \Exception('This method is only available for student requirement bookings.');
        }

        if ($booking->status === 'expired' || ($booking->expires_at && $booking->expires_at < now())) {
            if ($booking->status === 'pending') {
                DB::transaction(function () use ($booking) {
                    $booking->transitionTo('expired');
                });

                $booking->user->notify(new \App\Notifications\BookingNotification(
                    'Booking Expired',
                    'Your booking request has expired because the document was not uploaded within 10 minutes.',
                    'warning',
                    $booking->id
                ));

                ActivityLog::create([
                    'type' => 'danger',
                    'title' => 'Booking Expired',
                    'description' => "Booking {$booking->booking_number} has expired (10-minute upload deadline missed).",
                    'user_name' => $booking->user->name,
                    'user_id' => $booking->user_id,
                ]);
            }
            throw new \Exception('The upload deadline for requirement documents (10 minutes) has expired.');
        }

        if ($booking->status !== 'pending') {
            throw new \Exception('Can only upload file for pending booking');
        }

        $result = $this->uploadDokumen($file);

        $booking->update([
            'file_url' => $result['url'],
            'expires_at' => now()->addHours(2),
        ]);

        Media::create([
            'user_id' => $booking->user_id,
            'model_type' => Booking::class,
            'model_id' => $booking->id,
            'collection_name' => 'booking_document',
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $result['stored_path'],
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'bucket' => config('supabase.bucket_document', 'File-Document'),
            'url' => $result['url'],
        ]);

        return $booking;
    }

    public function uploadDokumen(\Illuminate\Http\UploadedFile $pdf): array
    {
        $filename = 'dokumen_' . uniqid() . '.pdf';
        $storagePath = "dokumen/{$filename}";

        $url = $this->supabaseService->upload(
            file: $pdf,
            storagePath: $storagePath,
            mimeType: 'application/pdf',
            binaryContent: null,
            bucket: config('supabase.bucket_document', 'File-Document')
        );

        return [
            'url' => $url,
            'stored_path' => $storagePath,
        ];
    }

    public function getAllBookings($filters = [], $perPage = 10)
    {
        $this->cleanupExpiredBookings();

        $query = Booking::with(['schedules.field', 'user', 'rating']);

        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['booking_type']) && !empty($filters['booking_type'])) {
            $query->where('booking_type', $filters['booking_type']);
        }

        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['field_id']) && !empty($filters['field_id'])) {
            $query->whereHas('schedules', function ($q) use ($filters) {
                $q->where('field_id', $filters['field_id']);
            });
        }

        if (isset($filters['date']) && !empty($filters['date'])) {
            $query->whereHas('schedules', function ($q) use ($filters) {
                $q->where('date', $filters['date']);
            });
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function approveBooking(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            throw new \Exception('Can only approve pending booking');
        }

        if ($booking->expires_at && $booking->expires_at < now()) {
            $booking->transitionTo('expired');
            throw new \Exception('Booking has expired');
        }

        $booking = DB::transaction(function () use ($booking) {
            $booking->transitionTo('approved');
            return $booking;
        });

        $firstSchedule = $booking->schedules->sortBy('start_time')->first();
        $lastSchedule = $booking->schedules->sortBy('start_time')->last();
        $fieldName = $firstSchedule->field->name ?? 'Lapangan';
        $startTime = $firstSchedule ? date('H:i', strtotime($firstSchedule->start_time)) : '';
        $endTime = $lastSchedule ? date('H:i', strtotime($lastSchedule->end_time)) : '';

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Booking Confirmed',
            "Booking {$fieldName} at {$startTime} - {$endTime} has been confirmed.",
            'success',
            $booking->id
        ));

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Document Verified',
            'Admin has approved your requirement document. Please check your booking history.',
            'info',
            $booking->id
        ));

        ActivityLog::create([
            'type' => 'success',
            'title' => 'Booking Confirmed',
            'description' => "Booking {$booking->booking_number} has been approved by Admin.",
            'user_name' => $booking->user->name,
            'user_id' => $booking->user_id,
        ]);

        return $booking;
    }

    public function rejectBooking(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            throw new \Exception('Can only reject pending booking');
        }

        $booking = DB::transaction(function () use ($booking) {
            $booking->transitionTo('rejected');
            return $booking;
        });

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Booking Rejected',
            'Sorry, your booking request has been rejected by Admin because the document did not meet the requirements.',
            'warning',
            $booking->id
        ));

        ActivityLog::create([
            'type' => 'danger',
            'title' => 'Booking Rejected',
            'description' => "Booking {$booking->booking_number} has been rejected by Admin.",
            'user_name' => $booking->user->name,
            'user_id' => $booking->user_id,
        ]);

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

        ActivityLog::create([
            'type' => 'success',
            'title' => 'Attendance Recorded',
            'description' => "User {$booking->user->name} attended booking session {$booking->booking_number}.",
            'user_name' => $booking->user->name,
            'user_id' => $booking->user_id,
        ]);

        return $booking;
    }

    public function getExpiredBookings()
    {
        return Booking::with(['schedules.field', 'user'])
            ->expired()
            ->get();
    }

    public function rescheduleBooking(User $user, int $bookingId, int $fieldId, string $date, string $newStartTimeSlot)
    {
        $booking = Booking::with('schedules')->where('id', $bookingId)->where('user_id', $user->id)->firstOrFail();

        if (Reschedule::where('booking_id', $bookingId)->exists()) {
            throw new \Exception('This booking has already been rescheduled. Rescheduling is limited to 1 time only.');
        }

        if (!$user->hasRole('mahasiswa')) {
            throw new \Exception('Free reschedule is only available for students.');
        }

        if (!in_array($booking->status, ['pending', 'approved'])) {
            throw new \Exception('Only active bookings can be rescheduled.');
        }

        $numSlots = $booking->schedules->count();
        if ($numSlots === 0) {
            throw new \Exception('Booking has no valid schedules.');
        }

        // Cek waktu jadwal mulai paling awal
        $firstSchedule = $booking->schedules->sortBy(function ($schedule) {
            return $schedule->date . ' ' . $schedule->start_time;
        })->first();

        $scheduleDateTime = \Carbon\Carbon::parse($firstSchedule->date . ' ' . $firstSchedule->start_time);
        if (now()->diffInHours($scheduleDateTime, false) < 2) {
            throw new \Exception('Reschedule can only be requested up to 2 hours before the schedule starts.');
        }

        // Generate consecutive slots
        $startHour = (int) substr($newStartTimeSlot, 0, 2);
        $newSlots = [];
        for ($i = 0; $i < $numSlots; $i++) {
            $hour = $startHour + $i;
            if ($hour >= 24) {
                throw new \Exception('Jadwal baru melewati batas waktu operasional (24:00).');
            }
            $newSlots[] = sprintf('%02d:00', $hour);
        }

        // Validasi ketersediaan semua slot
        foreach ($newSlots as $slot) {
            if (!$this->scheduleService->isSlotAvailable($fieldId, $date, $slot)) {
                throw new \Exception("Sesi pada jam {$slot} tidak tersedia.");
            }
        }

        return DB::transaction(function () use ($booking, $fieldId, $date, $newSlots) {
            $sortedOldSchedules = $booking->schedules->sortBy(function ($schedule) {
                return $schedule->date . ' ' . $schedule->start_time;
            })->values();

            $newScheduleIds = [];

            for ($i = 0; $i < $sortedOldSchedules->count(); $i++) {
                $oldSchedule = $sortedOldSchedules[$i];
                $newSlot = $newSlots[$i];

                // Buat schedule baru
                $newSchedule = $this->scheduleService->createScheduleForBooking($fieldId, $date, $newSlot);
                $newScheduleIds[] = $newSchedule->id;

                // Update detail
                BookingDetail::query()->where('booking_id', $booking->id)
                    ->where('schedule_id', $oldSchedule->id)
                    ->update(['schedule_id' => $newSchedule->id]);

                // Buat history reschedule untuk slot ini
                Reschedule::create([
                    'booking_id' => $booking->id,
                    'old_schedule_id' => $oldSchedule->id,
                    'new_schedule_id' => $newSchedule->id,
                ]);
            }

            // Update total price booking
            $schedules = Schedule::query()->findMany($newScheduleIds);
            $newTotalPrice = $schedules->sum('price');
            $booking->update(['total_price' => $newTotalPrice]);

            return $booking;
        });
    }

    public function cancelBooking(User $user, int $bookingId)
    {
        $booking = Booking::with('schedules')->where('id', $bookingId)->where('user_id', $user->id)->firstOrFail();

        $isMahasiswa = $user->hasRole('mahasiswa');
        $isUmum = $user->hasRole('umum');

        if (!$isMahasiswa && !$isUmum) {
            throw new \Exception('Your account does not have permission to cancel bookings.');
        }

        if (!in_array($booking->status, ['pending', 'approved'])) {
            throw new \Exception('Only active bookings can be cancelled.');
        }

        foreach ($booking->schedules as $schedule) {
            $scheduleDateTime = \Carbon\Carbon::parse($schedule->date . ' ' . $schedule->start_time);
            if (now()->diffInHours($scheduleDateTime, false) < 2) {
                throw new \Exception('Cancellation can only be requested up to 2 hours before the schedule starts.');
            }
        }

        if ($booking->status === 'cancelled') {
            throw new \Exception('This booking has already been cancelled.');
        }

        if ($booking->status === 'pending' && $booking->booking_type === 'paid' && $booking->qr_id) {
            $this->midtransService->cancelTransaction($booking->qr_id);
        }

        ActivityLog::create([
            'type' => 'warning',
            'title' => 'Cancellation',
            'description' => "Booking {$booking->booking_number} was cancelled by the user.",
            'user_name' => $user->name,
            'user_id' => $user->id,
        ]);

        return DB::transaction(function () use ($booking) {
            $booking->transitionTo('cancelled');
            return $booking;
        });
    }

    public function approvePaidBooking(Booking $booking): Booking
    {
        if ($booking->status === 'approved') {
            return $booking;
        }

        $booking = DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'approved',
                'expires_at' => null,
            ]);

            return $booking;
        });

        $firstSchedule = $booking->schedules->sortBy('start_time')->first();
        $lastSchedule = $booking->schedules->sortBy('start_time')->last();
        $fieldName = $firstSchedule->field->name ?? 'Lapangan';
        $startTime = $firstSchedule ? date('H:i', strtotime($firstSchedule->start_time)) : '';
        $endTime = $lastSchedule ? date('H:i', strtotime($lastSchedule->end_time)) : '';

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Booking Confirmed',
            "Booking {$fieldName} at {$startTime} - {$endTime} has been confirmed.",
            'success',
            $booking->id
        ));

        ActivityLog::create([
            'type' => 'success',
            'title' => 'Booking Confirmed',
            'description' => "Payment for booking {$booking->booking_number} completed (settlement).",
            'user_name' => $booking->user->name,
            'user_id' => $booking->user_id,
        ]);

        return $booking;
    }

    public function failPaidBooking(Booking $booking, string $reason = 'expired'): Booking
    {
        if ($booking->status === 'rejected' || $booking->status === 'cancelled') {
            return $booking;
        }

        $booking = DB::transaction(function () use ($booking, $reason) {
            $status = $reason === 'cancel' ? 'cancelled' : ($reason === 'expire' || $reason === 'expired' ? 'expired' : 'rejected');
            $booking->transitionTo($status);
            return $booking;
        });

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Booking Cancelled',
            "Your booking was cancelled due to {$reason} payment.",
            'warning',
            $booking->id
        ));

        ActivityLog::create([
            'type' => 'danger',
            'title' => 'Booking Failed',
            'description' => "Booking {$booking->booking_number} failed/expired during payment.",
            'user_name' => $booking->user->name,
            'user_id' => $booking->user_id,
        ]);

        return $booking;
    }

    public function cleanupExpiredBookings(): void
    {
        $expiredBookings = Booking::with('user')->where('status', 'pending')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking) {
                $booking->transitionTo('expired');
            });

            // Kirim notifikasi
            $message = $booking->booking_type === 'paid'
                ? 'Your booking payment has expired.'
                : 'Your booking request has expired because it was not verified by admin within 2 hours.';

            if ($booking->user) {
                $booking->user->notify(new \App\Notifications\BookingNotification(
                    'Booking Expired',
                    $message,
                    'warning',
                    $booking->id
                ));
            }

            // Catat log
            ActivityLog::create([
                'type' => 'danger',
                'title' => 'Booking Expired',
                'description' => "Booking {$booking->booking_number} has expired.",
                'user_name' => $booking->user->name ?? 'System',
                'user_id' => $booking->user_id,
            ]);
        }

        // Sync pending paid bookings directly with Midtrans to release slots if payment has expired/failed in Midtrans
        $pendingPaidBookings = Booking::where('status', 'pending')
            ->where('booking_type', 'paid')
            ->whereNotNull('qr_id')
            ->get();

        foreach ($pendingPaidBookings as $booking) {
            try {
                $midtransStatus = $this->midtransService->checkTransactionStatus($booking->qr_id);
                if ($midtransStatus) {
                    $transactionStatus = $midtransStatus->transaction_status ?? null;
                    if (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                        $this->failPaidBooking($booking, $transactionStatus);
                    } else if ($transactionStatus == 'settlement' || ($transactionStatus == 'capture' && ($midtransStatus->fraud_status ?? null) == 'accept')) {
                        $this->approvePaidBooking($booking);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync Midtrans status for booking {$booking->id} during cleanup: " . $e->getMessage());
            }
        }
    }

    public function triggerPendingRatingNotifications(User $user): void
    {
        $bookings = Booking::with(['schedules', 'rating'])
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDoesntHave('rating')
            ->get();

        foreach ($bookings as $booking) {
            $lastSchedule = $booking->schedules->sortByDesc(function ($schedule) {
                return $schedule->date . ' ' . $schedule->end_time;
            })->first();

            if ($lastSchedule) {
                $endDateTime = \Carbon\Carbon::parse($lastSchedule->date . ' ' . $lastSchedule->end_time);
                if (now()->gt($endDateTime)) {
                    $notificationExists = $user->notifications
                        ->contains(function ($notification) use ($booking) {
                            $data = $notification->data;
                            return isset($data['booking_id']) && $data['booking_id'] == $booking->id
                                && isset($data['type']) && $data['type'] === 'rating_reminder';
                        });

                    if (!$notificationExists) {
                        $user->notify(new \App\Notifications\BookingNotification(
                            'Beri Penilaian Lapangan',
                            "Waktu bermain Anda untuk booking {$booking->booking_number} telah selesai. Berikan penilaian Anda sekarang!",
                            'rating_reminder',
                            $booking->id
                        ));
                    }
                }
            }
        }
    }
}