<?php

namespace App\Observers;

use App\Models\Field;
use App\Services\ScheduleService;

class FieldObserver
{
    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function created(Field $field)
    {
        $this->scheduleService->generateSchedulesForField($field, 30);
    }
}