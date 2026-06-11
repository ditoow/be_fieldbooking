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
        if ($user->isSuspended()) {
            throw new \Exception('Akun Anda ditangguhkan sementara dari pembuatan booking baru.');
        }

        if (empty($timeSlots)) {
            throw new \Exception('Anda harus memilih minimal satu slot jadwal.');
        }

        // Validasi field ada
        $field = Field::findOrFail($fieldId);

        $isMahasiswa = $user->hasRole('mahasiswa');
        $isUmum = $user->hasRole('umum');

        if (!$isMahasiswa && !$isUmum) {
            throw new \Exception('User must have role mahasiswa or umum');
        }

        if ($isMahasiswa && count($timeSlots) > 3) {
            throw new \Exception('Mahasiswa hanya diperbolehkan memesan maksimal 3 jam dalam satu pemesanan.');
        }

        // Validasi semua slot available
        foreach ($timeSlots as $startTime) {
            if (!$this->scheduleService->isSlotAvailable($fieldId, $date, $startTime)) {
                throw new \Exception("Slot jadwal tanggal {$date} pukul {$startTime} tidak tersedia.");
            }
        }

        // Hitung total harga
        $totalPrice = 0;
        foreach ($timeSlots as $startTime) {
            $hour = (int) substr($startTime, 0, 2);
            $totalPrice += ($hour >= 16) ? 50000 : 40000;
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
                        'qr_string' => (isset($midtransResult['actions'][0]->url) ? $midtransResult['actions'][0]->url : null) ?? ($midtransResult['qr_string'] ?? null),
                    ]);
                }
            } catch (\Exception $e) {
                DB::transaction(function () use ($booking) {
                    $booking->update(['status' => 'rejected']);
                });
                throw $e;
            }

            $booking->load('schedules.field');
            $firstSchedule = $booking->schedules->sortBy('start_time')->first();
            $lastSchedule = $booking->schedules->sortBy('start_time')->last();
            $fieldName = $firstSchedule->field->name ?? 'Lapangan';
            $startTime = $firstSchedule ? date('H:i', strtotime($firstSchedule->start_time)) : '';
            $endTime = $lastSchedule ? date('H:i', strtotime($lastSchedule->end_time)) : '';

            $user->notify(new \App\Notifications\BookingNotification(
                'Menunggu Pembayaran',
                "Booking lapangan {$fieldName} pukul {$startTime} - {$endTime} berhasil dibuat. Silakan selesaikan pembayaran Anda.",
                'info',
                $booking->id
            ));
        }

        ActivityLog::create([
            'type' => 'info',
            'title' => 'Booking Baru',
            'description' => "Booking {$booking->booking_number} dibuat oleh {$user->name} untuk tanggal {$date}.",
            'user_name' => $user->name,
        ]);

        return $booking;
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
        $booking = Booking::with(['schedules.field', 'user'])
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
                $schedule->delete();
            }
            throw new \Exception('Batas waktu mengunggah berkas persyaratan (10 menit) telah habis.');
        }

        $fileUrl = $this->uploadDokumen($file);

        $booking->update([
            'file_url' => $fileUrl,
            'expires_at' => now()->addHours(2),
        ]);

        return $booking;
    }

    public function uploadDokumen(\Illuminate\Http\UploadedFile $pdf): string
    {
        $filename = 'dokumen_' . uniqid() . '.pdf';

        return $this->supabaseService->upload(
            file: $pdf,
            storagePath: "dokumen/{$filename}",
            mimeType: 'application/pdf',
            binaryContent: null,
            bucket: config('supabase.bucket_document', 'File-Document')
        );
    }

    public function getAllBookings($filters = [], $perPage = 10)
    {
        $query = Booking::with(['schedules.field', 'user']);

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

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
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

        $booking = DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'approved']);
            return $booking;
        });

        $firstSchedule = $booking->schedules->sortBy('start_time')->first();
        $lastSchedule = $booking->schedules->sortBy('start_time')->last();
        $fieldName = $firstSchedule->field->name ?? 'Lapangan';
        $startTime = $firstSchedule ? date('H:i', strtotime($firstSchedule->start_time)) : '';
        $endTime = $lastSchedule ? date('H:i', strtotime($lastSchedule->end_time)) : '';

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Pemesanan Berhasil',
            "Booking lapangan {$fieldName} pukul {$startTime} - {$endTime} telah dikonfirmasi.",
            'success',
            $booking->id
        ));

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Verifikasi Dokumen',
            'Admin telah menyetujui berkas Surat TU Anda. Silakan cek riwayat booking.',
            'info',
            $booking->id
        ));

        ActivityLog::create([
            'type' => 'success',
            'title' => 'Booking Dikonfirmasi',
            'description' => "Pemesanan {$booking->booking_number} telah disetujui oleh Admin.",
            'user_name' => $booking->user->name,
        ]);

        return $booking;
    }

    public function rejectBooking(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            throw new \Exception('Can only reject pending booking');
        }

        $booking = DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'rejected']);
            return $booking;
        });

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Pemesanan Ditolak',
            'Mohon maaf, pengajuan booking lapangan Anda ditolak oleh Admin karena dokumen berkas tidak memenuhi syarat.',
            'warning',
            $booking->id
        ));

        ActivityLog::create([
            'type' => 'danger',
            'title' => 'Pemesanan Ditolak',
            'description' => "Pemesanan {$booking->booking_number} ditolak oleh Admin.",
            'user_name' => $booking->user->name,
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
            'title' => 'Kehadiran Dicatat',
            'description' => "User {$booking->user->name} tercatat menghadiri sesi booking {$booking->booking_number}.",
            'user_name' => $booking->user->name,
        ]);

        return $booking;
    }

    public function getExpiredBookings()
    {
        return Booking::with(['schedules.field', 'user'])
            ->expired()
            ->get();
    }

    public function rescheduleBooking(User $user, $bookingId, $oldScheduleId, int $fieldId, string $date, string $newTimeSlot)
    {
        $booking = Booking::with('schedules')->where('id', $bookingId)->where('user_id', $user->id)->firstOrFail();

        if (!$user->hasRole('mahasiswa')) {
            throw new \Exception('Fitur reschedule gratis hanya tersedia untuk mahasiswa.');
        }

        if (!in_array($booking->status, ['pending', 'approved'])) {
            throw new \Exception('Hanya pemesanan aktif yang dapat dipindahkan.');
        }

        $oldSchedule = $booking->schedules->where('id', $oldScheduleId)->first();
        if (!$oldSchedule) {
            throw new \Exception('Jadwal lama tidak ditemukan dalam pemesanan ini.');
        }

        $scheduleDateTime = \Carbon\Carbon::parse($oldSchedule->date . ' ' . $oldSchedule->start_time);
        if (now()->diffInHours($scheduleDateTime, false) < 2) {
            throw new \Exception('Reschedule hanya dapat diajukan maksimal 2 jam sebelum jadwal dimulai.');
        }

        // Cek slot baru available
        if (!$this->scheduleService->isSlotAvailable($fieldId, $date, $newTimeSlot)) {
            throw new \Exception('Jadwal baru pilihan Anda tidak tersedia.');
        }

        return DB::transaction(function () use ($booking, $oldSchedule, $fieldId, $date, $newTimeSlot) {
            $oldScheduleId = $oldSchedule->id;

            // Buat schedule baru on-demand
            $newSchedule = $this->scheduleService->createScheduleForBooking($fieldId, $date, $newTimeSlot);

            BookingDetail::where('booking_id', $booking->id)
                ->where('schedule_id', $oldScheduleId)
                ->update(['schedule_id' => $newSchedule->id]);

            // Update total price
            $currentScheduleIds = $booking->schedules()->pluck('schedules.id')->toArray();
            $key = array_search($oldScheduleId, $currentScheduleIds);
            if ($key !== false) {
                $currentScheduleIds[$key] = $newSchedule->id;
            }
            $newTotalPrice = Schedule::whereIn('id', $currentScheduleIds)->sum('price');
            $booking->update(['total_price' => $newTotalPrice]);

            Reschedule::create([
                'booking_id' => $booking->id,
                'old_schedule_id' => $oldScheduleId,
                'new_schedule_id' => $newSchedule->id,
            ]);

            return $booking;
        });
    }

    public function cancelBooking(User $user, $bookingId)
    {
        $booking = Booking::with('schedules')->where('id', $bookingId)->where('user_id', $user->id)->firstOrFail();

        $isMahasiswa = $user->hasRole('mahasiswa');
        $isUmum = $user->hasRole('umum');

        if (!$isMahasiswa && !$isUmum) {
            throw new \Exception('Akun Anda tidak memiliki akses untuk membatalkan pesanan.');
        }

        if (!in_array($booking->status, ['pending', 'approved'])) {
            throw new \Exception('Hanya pemesanan aktif yang dapat dibatalkan.');
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

        if ($booking->status === 'pending' && $booking->booking_type === 'paid' && $booking->qr_id) {
            $this->midtransService->cancelTransaction($booking->qr_id);
        }

        ActivityLog::create([
            'type' => 'warning',
            'title' => 'Pembatalan',
            'description' => "Pemesanan {$booking->booking_number} dibatalkan oleh pengguna.",
            'user_name' => $user->name,
        ]);

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);
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
            'Pemesanan Berhasil',
            "Booking lapangan {$fieldName} pukul {$startTime} - {$endTime} telah dikonfirmasi.",
            'success',
            $booking->id
        ));

        ActivityLog::create([
            'type' => 'success',
            'title' => 'Booking Dikonfirmasi',
            'description' => "Pembayaran untuk pemesanan {$booking->booking_number} lunas (settlement).",
            'user_name' => $booking->user->name,
        ]);

        return $booking;
    }

    public function failPaidBooking(Booking $booking, string $reason = 'expired'): Booking
    {
        if ($booking->status === 'rejected' || $booking->status === 'cancelled') {
            return $booking;
        }

        $booking = DB::transaction(function () use ($booking, $reason) {
            $status = $reason === 'cancel' ? 'cancelled' : 'rejected';
            $booking->update(['status' => $status]);
            return $booking;
        });

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Pemesanan Dibatalkan',
            "Booking lapangan Anda dibatalkan karena pembayaran {$reason}.",
            'warning',
            $booking->id
        ));

        ActivityLog::create([
            'type' => 'danger',
            'title' => 'Pemesanan Gagal',
            'description' => "Pemesanan {$booking->booking_number} gagal/expired dalam proses pembayaran.",
            'user_name' => $booking->user->name,
        ]);

        return $booking;
    }
}