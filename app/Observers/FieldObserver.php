<?php

namespace App\Observers;

use App\Models\Field;
use App\Models\Schedule;

class FieldObserver
{
    public function created(Field $field)
    {
        $this->generateSchedules($field, 30);
    }

    public function generateSchedules(Field $field, $days = 30)
    {
        for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
            $date = now()->addDays($dayOffset)->format('Y-m-d');

            for ($hour = 6; $hour < 24; $hour++) {
                $price = ($hour >= 16) ? 50000 : 40000;

                Schedule::create([
                    'field_id' => $field->id,
                    'date' => $date,
                    'start_time' => sprintf('%02d:00', $hour),
                    'end_time' => sprintf('%02d:00', $hour + 1),
                    'price' => $price,
                    'status' => 'available',
                ]);
            }
        }
    }
}