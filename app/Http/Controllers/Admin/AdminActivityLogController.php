<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;

class AdminActivityLogController extends Controller
{
    public function index(): JsonResponse
    {
        $logs = ActivityLog::with('user')->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $formattedLogs = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'type' => $log->type,
                'title' => $log->title,
                'user_name' => $log->user?->name ?? $log->user_name,
                'description' => $log->description,
                'time_ago' => $log->created_at ? $log->created_at->diffForHumans() : 'just now',
                'created_at' => $log->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Activity logs retrieved successfully',
            'data' => $formattedLogs,
        ]);
    }
}
