<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'booking_type', 'user_id']);
        $bookings = $this->bookingService->getAllBookings($filters);

        return BookingResource::collection($bookings);
    }

    public function approve($id)
    {
        $booking = \App\Models\Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        try {
            $booking = $this->bookingService->approveBooking($booking);
            return response()->json([
                'message' => 'Booking approved successfully',
                'data' => new BookingResource($booking->load(['schedules.field', 'user'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reject($id)
    {
        $booking = \App\Models\Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        try {
            $booking = $this->bookingService->rejectBooking($booking);
            return response()->json([
                'message' => 'Booking rejected successfully',
                'data' => new BookingResource($booking->load(['schedules.field', 'user'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function attend($id)
    {
        $booking = \App\Models\Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        try {
            $booking = $this->bookingService->markAttendance($booking);
            return response()->json([
                'message' => 'Attendance marked successfully',
                'data' => new BookingResource($booking->load(['schedules.field', 'user'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}