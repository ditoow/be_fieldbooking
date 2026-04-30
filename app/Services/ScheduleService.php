<?php

namespace App\Services;

use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleService
{
    public function createBulkSchedule($data)
    {
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
