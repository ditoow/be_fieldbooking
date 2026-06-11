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
        $isFullDayMaintenance = FieldMaintenance::where('field_id', $fieldId)
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
                    'price' => ($hour >= 16) ? 50000 : 40000,
                    'status' => 'maintenance',
                ];
            }
            return $slots;
        }

        // Ambil jam-jam yang di-maintenance (partial)
        $maintenanceSlots = $this->getMaintenanceSlots($fieldId, $date);

        // Ambil jam-jam yang sudah dibooking dengan booking aktif (pending/approved)
        $bookedSlots = Schedule::where('field_id', $fieldId)
            ->where('date', $date)
            ->whereHas('bookings', function ($query) {
                $query->whereIn('status', ['pending', 'approved']);
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
                'price' => ($hour >= 16) ? 50000 : 40000,
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
        $isFullDayMaintenance = FieldMaintenance::where('field_id', $fieldId)
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
        $isBooked = Schedule::where('field_id', $fieldId)
            ->where('date', $date)
            ->where('start_time', $startTime)
            ->whereHas('bookings', function ($query) {
                $query->whereIn('status', ['pending', 'approved']);
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

        return Schedule::firstOrCreate([
            'field_id' => $fieldId,
            'date' => $date,
            'start_time' => $startTime,
        ], [
            'end_time' => sprintf('%02d:00', $hour + 1),
            'price' => ($hour >= 16) ? 50000 : 40000,
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
        $maintenances = FieldMaintenance::where('field_id', $fieldId)
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
