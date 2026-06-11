<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
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
            'field_id' => 'required|exists:fields,id',
            'date' => 'required|date|after_or_equal:today',
            'time_slots' => 'required|array|min:1',
            'time_slots.*' => 'string|regex:/^\d{2}:00$/',
        ]);

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
            'file' => 'required|file|mimes:pdf|max:1024',
        ]);

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

    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'old_schedule_id' => 'required|exists:schedules,id',
            'field_id' => 'required|exists:fields,id',
            'date' => 'required|date|after_or_equal:today',
            'new_time_slot' => 'required|string|regex:/^\d{2}:00$/',
        ]);

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
                'message' => 'Jadwal pemesanan berhasil dipindahkan!',
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
                'message' => 'Pemesanan berhasil dibatalkan!',
                'data' => new BookingResource($booking->load(['schedules.field', 'user'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
