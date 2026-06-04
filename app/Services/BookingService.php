<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Reschedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookingService
{
    protected SupabaseService $supabaseService;
    protected MidtransService $midtransService;

    public function __construct(SupabaseService $supabaseService, MidtransService $midtransService)
    {
        $this->supabaseService = $supabaseService;
        $this->midtransService = $midtransService;
    }
    public function createBooking(User $user, array $scheduleIds)
    {
        if ($user->isSuspended()) {
            throw new \Exception('Akun Anda ditangguhkan sementara dari pembuatan booking baru.');
        }

        if (empty($scheduleIds)) {
            throw new \Exception('Anda harus memilih minimal satu slot jadwal.');
        }

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

        
        if ($isMahasiswa && count($scheduleIds) > 3) {
            throw new \Exception('Mahasiswa hanya diperbolehkan memesan maksimal 3 jam dalam satu pemesanan.');
        }


        $fieldIds = $schedules->pluck('field_id')->unique();
        if ($fieldIds->count() > 1) {
            throw new \Exception('Semua slot jam yang dipesan harus berada di lapangan yang sama.');
        }


        $dates = $schedules->pluck('date')->unique();
        if ($dates->count() > 1) {
            throw new \Exception('Semua slot jam yang dipesan harus berada di tanggal yang sama.');
        }


        $totalPrice = $schedules->sum('price');

        $booking = DB::transaction(function () use ($user, $schedules, $isMahasiswa, $isUmum, $totalPrice) {
            $booking = Booking::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'booking_type' => $isUmum ? 'paid' : 'requirement',
                'expires_at' => $isMahasiswa ? now()->addMinutes(10) : now()->addMinutes(30),
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
                    foreach ($booking->schedules as $schedule) {
                        $schedule->update(['status' => 'available']);
                    }
                });
                throw $e;
            }

            $firstSchedule = $schedules->sortBy('start_time')->first();
            $lastSchedule = $schedules->sortBy('start_time')->last();
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
                $schedule->update(['status' => 'available']);
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
            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'available']);
            }
            throw new \Exception('Booking has expired');
        }

        $booking = DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'approved']);

            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'booked']);
            }

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

        return $booking;
    }

    public function rejectBooking(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            throw new \Exception('Can only reject pending booking');
        }

        $booking = DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'rejected']);

            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'available']);
            }

            return $booking;
        });

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Pemesanan Ditolak',
            'Mohon maaf, pengajuan booking lapangan Anda ditolak oleh Admin karena dokumen berkas tidak memenuhi syarat.',
            'warning',
            $booking->id
        ));

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

        if (!in_array($booking->status, ['pending', 'approved'])) {
            throw new \Exception('Hanya pemesanan aktif yang dapat dipindahkan.');
        }
        if ((int) $oldScheduleId === (int) $newScheduleId) {
            throw new \Exception('Jadwal baru tidak boleh sama dengan jadwal lama.');
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
            $currentScheduleIds = $booking->schedules->pluck('id')->toArray();
            $key = array_search($oldSchedule->id, $currentScheduleIds);
            if ($key !== false) {
                $currentScheduleIds[$key] = $newSchedule->id;
            }
            $newTotalPrice = Schedule::whereIn('id', $currentScheduleIds)->sum('price');
            $booking->update(['total_price' => $newTotalPrice]);

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

        // Jika pesanan berbayar masih pending (belum dibayar) dan punya ID transaksi Midtrans, batalkan di Midtrans
        if ($booking->status === 'pending' && $booking->booking_type === 'paid' && $booking->qr_id) {
            $this->midtransService->cancelTransaction($booking->qr_id);
        }

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);

            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'available']);
            }

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

            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'booked']);
            }

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

            foreach ($booking->schedules as $schedule) {
                $schedule->update(['status' => 'available']);
            }

            return $booking;
        });

        $booking->user->notify(new \App\Notifications\BookingNotification(
            'Pemesanan Dibatalkan',
            "Booking lapangan Anda dibatalkan karena pembayaran {$reason}.",
            'warning',
            $booking->id
        ));

        return $booking;
    }
}