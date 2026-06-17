<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Services\BookingService;

class NotificationController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index()
    {
        $user = Auth::guard('api')->user();

        $this->bookingService->triggerPendingRatingNotifications($user);

        $notifications = $user->notifications()->orderBy('created_at', 'desc')->get();

        return NotificationResource::collection($notifications);
    }

    public function read(string $id)
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
