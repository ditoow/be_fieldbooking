<?php

namespace App\Services;

use App\Models\Field;
use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleService
{
    public function generateSchedulesForField(Field $field, int $days = 30)
    {
        for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
            $date = now()->addDays($dayOffset)->format('Y-m-d');

            for ($hour = 6; $hour < 24; $hour++) {
                $startTime = sprintf('%02d:00', $hour);

                // Cek apakah jadwal sudah ada untuk menghindari duplikat
                $exists = Schedule::where('field_id', $field->id)
                    ->where('date', $date)
                    ->where('start_time', $startTime)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $price = ($hour >= 16) ? 50000 : 40000;

                Schedule::create([
                    'field_id' => $field->id,
                    'date' => $date,
                    'start_time' => $startTime,
                    'end_time' => sprintf('%02d:00', $hour + 1),
                    'price' => $price,
                    'status' => 'available',
                ]);
            }
        }
    }

    public function createBulkSchedule($data){
        
        $fieldId = $data['field_id'];
        $date = Carbon::parse($data['date'])->format('Y-m-d');
        $price = $data['price'];
        $startTime = Carbon::parse($data['start_time']); // misal: 08:00
        $endTime = Carbon::parse($data['end_time']);     // misal: 16:00

        $createdSchedules = [];

        // 3. Lakukan perulangan SELAMA jam mulai masih lebih kecil dari jam selesai
        while ($startTime->lt($endTime)) {
            
            $slotStart = $startTime->format('H:i'); 
            $startTime->addHour();                  
            $slotEnd = $startTime->format('H:i');   

            $schedule = Schedule::create([
                'field_id' => $fieldId,
                'date' => $date,
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'price' => $price,
                'status' => 'available',
            ]);

            $createdSchedules[] = $schedule;
        }

        return $createdSchedules;
    }

    public function deleteSchedule(Schedule $schedule){
        return $schedule->delete();
    }
}
