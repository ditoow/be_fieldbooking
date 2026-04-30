<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScheduleResource;
use App\Services\ScheduleService;
use App\Models\Schedule; // Jangan lupa import modelnya
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function store(Request $request){
        
        $validatedData = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',  
            'end_time' => 'required|date_format:H:i',   
            'price' => 'required|numeric',
        ]);


        $schedules = $this->scheduleService->createBulkSchedule($validatedData);
        return ScheduleResource::collection($schedules);
    }

    public function destroy(Schedule $schedule) 
    {
    
        $this->scheduleService->deleteSchedule($schedule); 

        return response()->json([
            'message' => 'Jadwal berhasil dihapus'
        ]);
    }
}
