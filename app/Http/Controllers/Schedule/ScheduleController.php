<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\IndexScheduleRequest;
use App\Services\ScheduleService;

class ScheduleController extends Controller
{
    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function index(IndexScheduleRequest $request)
    {
        $validated = $request->validated();
        $fieldId = (int) $validated['field_id'];
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        $result = [];
        $current = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        while ($current->lte($end)) {
            $date = $current->format('Y-m-d');
            $slots = $this->scheduleService->getAllSlots($fieldId, $date);

            $result[] = [
                'date' => $date,
                'slots' => $slots,
            ];

            $current->addDay();
        }

        return response()->json(['data' => $result]);
    }
}
