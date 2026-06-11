<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\FieldMaintenance;
use Illuminate\Http\Request;

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
    public function store(Request $request, $fieldId)
    {
        $field = Field::findOrFail($fieldId);

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:255',
        ]);

        // Jika start_time dan end_time null → full day maintenance
        // Jika keduanya ada → partial maintenance
        if (($request->start_time && !$request->end_time) || (!$request->start_time && $request->end_time)) {
            return response()->json([
                'message' => 'start_time dan end_time harus diisi keduanya, atau kosongkan keduanya untuk maintenance seharian.',
            ], 422);
        }

        $maintenance = FieldMaintenance::create([
            'field_id' => $fieldId,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'message' => 'Jadwal maintenance berhasil ditambahkan.',
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
            'message' => 'Jadwal maintenance berhasil dihapus.',
        ]);
    }
}
