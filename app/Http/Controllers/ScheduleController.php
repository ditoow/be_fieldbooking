<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'field_id' => 'required|exists:fields,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $fieldId = $request->field_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $schedules = Schedule::where('field_id', $fieldId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('date');

        $result = [];
        foreach ($schedules as $date => $slots) {
            $result[] = [
                'date' => $date,
                'slots' => $slots->map(function ($slot) {
                    return [
                        'id' => $slot->id,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'price' => $slot->price,
                        'status' => $slot->status,
                    ];
                }),
            ];
        }

        return response()->json(['data' => $result]);
    }
}