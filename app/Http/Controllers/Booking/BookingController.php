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
    protected $bookingService;

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
        $filters = $request->only(['status']);
        $perPage = $request->integer('per_page', 10);
        $bookings = $this->bookingService->getUserBookings($user, $filters, $perPage);

        return BookingResource::collection($bookings);
    }

    public function show($id)
    {
        $user = Auth::guard('api')->user();

        try {
            $booking = $this->bookingService->getBookingById($id, $user);
            return new BookingResource($booking);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Booking not found',
            ], 404);
        }
    }

    public function upload(UploadDocumentRequest $request, $id)
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

    public function reschedule(RescheduleBookingRequest $request, $id)
    {
        $user = Auth::guard('api')->user();

        try {
            $booking = $this->bookingService->rescheduleBooking(
                $user,
                $id,
                $request->old_schedule_id,
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

    public function cancel($id)
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
