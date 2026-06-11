<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Services\ScheduleService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function index(Request $request)
    {
        $request->validate([
            'field_id' => 'required|exists:fields,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $fieldId = (int) $request->field_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

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
