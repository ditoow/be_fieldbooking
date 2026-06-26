<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $dates = [];
        for ($i = 7; $i >= 1; $i--) {
            $dates[] = now()->subDays($i);
        }
        $dates[] = now();
        $dates[] = now()->addDay();

        $slotsByField = [
            1 => [
                ['start_time' => '09:00', 'end_time' => '10:00', 'price' => 40000],
                ['start_time' => '10:00', 'end_time' => '11:00', 'price' => 40000],
                ['start_time' => '11:00', 'end_time' => '12:00', 'price' => 40000],
                ['start_time' => '14:00', 'end_time' => '15:00', 'price' => 40000],
                ['start_time' => '15:00', 'end_time' => '16:00', 'price' => 40000],
                ['start_time' => '16:00', 'end_time' => '17:00', 'price' => 40000],
            ],
            2 => [
                ['start_time' => '09:00', 'end_time' => '10:00', 'price' => 40000],
                ['start_time' => '10:00', 'end_time' => '11:00', 'price' => 40000],
                ['start_time' => '11:00', 'end_time' => '12:00', 'price' => 40000],
                ['start_time' => '14:00', 'end_time' => '15:00', 'price' => 40000],
                ['start_time' => '15:00', 'end_time' => '16:00', 'price' => 40000],
                ['start_time' => '16:00', 'end_time' => '17:00', 'price' => 40000],
            ],
            3 => [
                ['start_time' => '08:00', 'end_time' => '09:00', 'price' => 40000],
                ['start_time' => '09:00', 'end_time' => '10:00', 'price' => 40000],
                ['start_time' => '10:00', 'end_time' => '11:00', 'price' => 40000],
                ['start_time' => '15:00', 'end_time' => '16:00', 'price' => 40000],
                ['start_time' => '16:00', 'end_time' => '17:00', 'price' => 40000],
            ],
            4 => [
                ['start_time' => '07:00', 'end_time' => '08:00', 'price' => 50000],
                ['start_time' => '08:00', 'end_time' => '09:00', 'price' => 50000],
                ['start_time' => '12:00', 'end_time' => '13:00', 'price' => 50000],
                ['start_time' => '13:00', 'end_time' => '14:00', 'price' => 50000],
                ['start_time' => '18:00', 'end_time' => '19:00', 'price' => 50000],
                ['start_time' => '19:00', 'end_time' => '20:00', 'price' => 50000],
            ],
            5 => [
                ['start_time' => '06:00', 'end_time' => '07:00', 'price' => 50000],
                ['start_time' => '07:00', 'end_time' => '08:00', 'price' => 50000],
                ['start_time' => '16:00', 'end_time' => '17:00', 'price' => 50000],
                ['start_time' => '17:00', 'end_time' => '18:00', 'price' => 50000],
                ['start_time' => '18:00', 'end_time' => '19:00', 'price' => 50000],
                ['start_time' => '20:00', 'end_time' => '21:00', 'price' => 50000],
            ],
        ];

        foreach ($dates as $date) {
            foreach ($slotsByField as $fieldId => $slots) {
                foreach ($slots as $slot) {
                    Schedule::firstOrCreate(
                        [
                            'field_id' => $fieldId,
                            'date' => $date->toDateString(),
                            'start_time' => $slot['start_time'] . ':00',
                        ],
                        [
                            'end_time' => $slot['end_time'] . ':00',
                            'price' => $slot['price'],
                        ]
                    );
                }
            }
        }
    }
}
