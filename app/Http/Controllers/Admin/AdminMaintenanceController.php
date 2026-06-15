<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMaintenanceRequest;
use App\Models\Field;
use App\Models\FieldMaintenance;

class AdminMaintenanceController extends Controller
{
    /**
     * List maintenance schedules untuk field tertentu.
     */
    public function index($fieldId)
    {
        $field = Field::findOrFail($fieldId);

        $maintenances = FieldMaintenance::where('field_id', $fieldId)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'data' => $maintenances,
        ]);
    }

    /**
     * Set tanggal/jam maintenance untuk field.
     */
    public function store(StoreMaintenanceRequest $request, $fieldId)
    {
        $field = Field::findOrFail($fieldId);
        $validated = $request->validated();

        // Jika start_time dan end_time null → full day maintenance
        // Jika keduanya ada → partial maintenance
        if (($validated['start_time'] && !$validated['end_time']) || (!$validated['start_time'] && $validated['end_time'])) {
            return response()->json([
            'message' => 'start_time and end_time must both be provided, or both empty for full-day maintenance.',
        ], 422);
        }

        $maintenance = FieldMaintenance::create([
            'field_id' => $fieldId,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Maintenance schedule added successfully.',
            'data' => $maintenance,
        ], 201);
    }

    /**
     * Hapus jadwal maintenance.
     */
    public function destroy($id)
    {
        $maintenance = FieldMaintenance::findOrFail($id);
        $maintenance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule deleted successfully.',
        ]);
    }
}
