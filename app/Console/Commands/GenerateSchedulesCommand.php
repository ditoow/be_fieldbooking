<?php

namespace App\Console\Commands;

use App\Models\Field;
use App\Services\ScheduleService;
use Illuminate\Console\Command;

class GenerateSchedulesCommand extends Command
{
    protected $signature = 'schedules:generate';
    protected $description = 'Generate schedules for all fields for the next 30 days';

    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        parent::__construct();
        $this->scheduleService = $scheduleService;
    }

    public function handle()
    {
        $fields = Field::all();

        foreach ($fields as $field) {
            $this->scheduleService->generateSchedulesForField($field, 30);
            $this->info("Generated schedules for field: {$field->name}");
        }

        $this->info('All schedules generated successfully!');
    }
}