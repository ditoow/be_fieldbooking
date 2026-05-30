<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::guard('api')->user();
        $notifications = $user->notifications()->orderBy('created_at', 'desc')->get();

        return NotificationResource::collection($notifications);
    }

    public function read($id)
    {
        $user = Auth::guard('api')->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read successfully',
            'data' => new NotificationResource($notification)
        ]);
    }

    public function readAll()
    {
        $user = Auth::guard('api')->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read successfully'
        ]);
    }
}
