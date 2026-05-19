<?php

namespace App\Console\Commands;

use App\Models\Field;
use App\Models\Schedule;
use Illuminate\Console\Command;

class GenerateSchedulesCommand extends Command
{
    protected $signature = 'schedules:generate';
    protected $description = 'Generate schedules for all fields for the next 30 days';

    public function handle()
    {
        $fields = Field::all();

        foreach ($fields as $field) {
            for ($dayOffset = 0; $dayOffset < 30; $dayOffset++) {
                $date = now()->addDays($dayOffset)->format('Y-m-d');

                for ($hour = 6; $hour < 24; $hour++) {
                    $exists = Schedule::where('field_id', $field->id)
                        ->where('date', $date)
                        ->where('start_time', sprintf('%02d:00', $hour))
                        ->exists();

                    if ($exists) {
                        continue;
                    }

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

            $this->info("Generated schedules for field: {$field->name}");
        }

        $this->info('All schedules generated successfully!');
    }
}