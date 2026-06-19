<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\RescheduleBookingRequest;
use App\Http\Requests\Booking\UploadDocumentRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function store(StoreBookingRequest $request)
    {
        $user = Auth::guard('api')->user();

        try {
            $booking = $this->bookingService->createBooking(
                $user,
                (int) $request->field_id,
                $request->date,
                $request->time_slots
            );
            return response()->json([
                'message' => 'Booking created successfully',
                'data' => new BookingResource($booking->load(['schedules.field', 'user'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        if ($user->hasRole('admin')) {
            $filters = $request->only(['status', 'booking_type', 'user_id', 'field_id', 'date', 'search']);
            $bookings = $this->bookingService->getAllBookings($filters);
        } else {
            $filters = $request->only(['status']);
            $bookings = $this->bookingService->getUserBookings($user, $filters);
        }

        return BookingResource::collection($bookings);
    }

    public function show(int $id)
    {
        $user = Auth::guard('api')->user();

        try {
            $booking = $this->bookingService->getBookingById($id, $user);
            return new BookingResource($booking);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Booking not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function upload(UploadDocumentRequest $request, int $id)
    {
        $user = Auth::guard('api')->user();

        try {
            $booking = $this->bookingService->getBookingById($id, $user);
            $booking = $this->bookingService->uploadFile($booking, $request->file('file'));

            return response()->json([
                'message' => 'File uploaded successfully',
                'data' => new BookingResource($booking->load(['schedules.field', 'user'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reschedule(RescheduleBookingRequest $request, int $id)
    {
        $user = Auth::guard('api')->user();

        try {
            $booking = $this->bookingService->rescheduleBooking(
                $user,
                $id,
                (int) $request->field_id,
                $request->date,
                $request->new_time_slot
            );
            return response()->json([
                'message' => 'Booking rescheduled successfully!',
                'data' => new BookingResource($booking->load(['schedules.field', 'user'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(int $id)
    {
        $user = Auth::guard('api')->user();

        try {
            $booking = $this->bookingService->cancelBooking($user, $id);
            return response()->json([
                'message' => 'Booking cancelled successfully!',
                'data' => new BookingResource($booking->load(['schedules.field', 'user'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
