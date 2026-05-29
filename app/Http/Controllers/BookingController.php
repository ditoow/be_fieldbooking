<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        $user = Auth::guard('api')->user();

        try {
            $booking = $this->bookingService->createBooking($user, $request->schedule_id);
            return response()->json([
                'message' => 'Booking created successfully',
                'data' => new BookingResource($booking->load(['schedule.field', 'user'])),
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
        $bookings = $this->bookingService->getUserBookings($user, $filters);

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

    public function upload(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = Auth::guard('api')->user();

        try {
            $booking = $this->bookingService->getBookingById($id, $user);
            $booking = $this->bookingService->uploadFile($booking, $request->file('file'));

            return response()->json([
                'message' => 'File uploaded successfully',
                'data' => new BookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        $user = Auth::guard('api')->user();

        try {
            $booking = $this->bookingService->rescheduleBooking($user, $id, $request->schedule_id);
            return response()->json([
                'message' => 'Jadwal pemesanan berhasil dipindahkan!',
                'data' => new BookingResource($booking->load(['schedule.field', 'user'])),
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
                'message' => 'Pemesanan berhasil dibatalkan!',
                'data' => new BookingResource($booking->load(['schedule.field', 'user'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}