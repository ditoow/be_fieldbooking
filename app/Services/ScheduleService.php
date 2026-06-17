<?php

namespace App\Services;

use App\Models\Field;
use App\Models\FieldMaintenance;
use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleService
{
    /**
     * Get semua slot untuk tanggal tertentu dengan status masing-masing.
     * Mengembalikan slot available, booked, dan maintenance.
     */
    public function getAllSlots(int $fieldId, string $date): array
    {
        $field = Field::with('detail')->findOrFail($fieldId);

        // Cek apakah tanggal ini full day maintenance
        $isFullDayMaintenance = FieldMaintenance::query()->where('field_id', $fieldId)
            ->where('date', $date)
            ->whereNull('start_time')
            ->exists();

        if ($isFullDayMaintenance) {
            // Semua slot maintenance
            $slots = [];
            for ($hour = 6; $hour < 24; $hour++) {
                $slots[] = [
                    'start_time' => sprintf('%02d:00', $hour),
                    'end_time' => sprintf('%02d:00', $hour + 1),
                    'price' => ($hour >= 16) ? config('pricing.after_16') : config('pricing.before_16'),
                    'status' => 'maintenance',
                ];
            }
            return $slots;
        }

        // Ambil jam-jam yang di-maintenance (partial)
        $maintenanceSlots = $this->getMaintenanceSlots($fieldId, $date);

        // Ambil jam-jam yang sudah dibooking dengan booking aktif (pending/approved)
        $bookedSlots = Schedule::query()->where('field_id', $fieldId)
            ->where('date', $date)
            ->whereHas('bookings', function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'approved')
                      ->orWhere(function ($qp) {
                          $qp->where('status', 'pending')
                             ->where(function ($qe) {
                                 $qe->whereNull('expires_at')
                                    ->orWhere('expires_at', '>=', now());
                             });
                      });
                });
            })
            ->pluck('start_time')
            ->map(fn($t) => substr($t, 0, 5))
            ->toArray();

        $slots = [];
        for ($hour = 6; $hour < 24; $hour++) {
            $startTime = sprintf('%02d:00', $hour);

            if (in_array($startTime, $bookedSlots)) {
                $status = 'booked';
            } elseif (in_array($startTime, $maintenanceSlots)) {
                $status = 'maintenance';
            } else {
                $status = 'available';
            }

            $slots[] = [
                'start_time' => $startTime,
                'end_time' => sprintf('%02d:00', $hour + 1),
                'price' => ($hour >= 16) ? config('pricing.after_16') : config('pricing.before_16'),
                'status' => $status,
            ];
        }

        return $slots;
    }

    /**
     * Cek apakah slot tertentu available untuk booking.
     */
    public function isSlotAvailable(int $fieldId, string $date, string $startTime): bool
    {
        // Cek full day maintenance
        $isFullDayMaintenance = FieldMaintenance::query()->where('field_id', $fieldId)
            ->where('date', $date)
            ->whereNull('start_time')
            ->exists();

        if ($isFullDayMaintenance) {
            return false;
        }

        // Cek partial maintenance
        $maintenanceSlots = $this->getMaintenanceSlots($fieldId, $date);
        if (in_array($startTime, $maintenanceSlots)) {
            return false;
        }

        // Cek sudah dibooking (aktif)
        $isBooked = Schedule::query()->where('field_id', $fieldId)
            ->where('date', $date)
            ->where('start_time', $startTime)
            ->whereHas('bookings', function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'approved')
                      ->orWhere(function ($qp) {
                          $qp->where('status', 'pending')
                             ->where(function ($qe) {
                                 $qe->whereNull('expires_at')
                                    ->orWhere('expires_at', '>=', now());
                             });
                      });
                });
            })
            ->exists();

        return !$isBooked;
    }

    /**
     * Buat schedule record saat booking (on-demand).
     */
    public function createScheduleForBooking(int $fieldId, string $date, string $startTime): Schedule
    {
        $hour = (int) substr($startTime, 0, 2);

        // Cek maintenance (tidak perlu lock karena jarang berubah)
        $isMaintenance = FieldMaintenance::query()->where('field_id', $fieldId)
            ->where('date', $date)
            ->where(function ($q) use ($startTime) {
                $q->whereNull('start_time')
                  ->orWhere('start_time', '<=', $startTime)
                  ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($isMaintenance) {
            throw new \Exception("Schedule slot on {$date} at {$startTime} is under maintenance.");
        }

        // Lock jadwal untuk cegah race condition
        $existing = Schedule::query()->where('field_id', $fieldId)
            ->where('date', $date)
            ->where('start_time', $startTime)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            // Cek apakah schedule masih terikat booking aktif
            $hasActiveBooking = $existing->bookings()
                ->where(function ($q) {
                    $q->where('status', 'approved')
                      ->orWhere(function ($qp) {
                          $qp->where('status', 'pending')
                             ->where(function ($qe) {
                                 $qe->whereNull('expires_at')
                                    ->orWhere('expires_at', '>=', now());
                             });
                      });
                })
                ->exists();

            if ($hasActiveBooking) {
                throw new \Exception("Schedule slot on {$date} at {$startTime} is not available.");
            }

            // Schedule hanya terikat booking expired/cancelled — re-use
            return $existing;
        }

        return Schedule::create([
            'field_id' => $fieldId,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => sprintf('%02d:00', $hour + 1),
            'price' => ($hour >= 16) ? config('pricing.after_16') : config('pricing.before_16'),
        ]);
    }

    /**
     * Hapus schedule record saat cancel/reject (kembalikan slot).
     * DIUBAH MENJADI NO-OP untuk menjaga riwayat pemesanan.
     */
    public function deleteSchedule(Schedule $schedule): void
    {
        // No-op to preserve booking history
    }

    /**
     * Ambil jam-jam yang di-maintenance secara partial pada tanggal tertentu.
     */
    protected function getMaintenanceSlots(int $fieldId, string $date): array
    {
        $maintenances = FieldMaintenance::query()->where('field_id', $fieldId)
            ->where('date', $date)
            ->whereNotNull('start_time')
            ->get();

        $slots = [];
        foreach ($maintenances as $m) {
            $start = Carbon::parse($m->start_time);
            $end = Carbon::parse($m->end_time);

            while ($start->lt($end)) {
                $slots[] = $start->format('H:i');
                $start->addHour();
            }
        }

        return $slots;
    }
}
