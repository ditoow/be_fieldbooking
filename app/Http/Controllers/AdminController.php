<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Field;
use App\Models\User;
use App\Http\Resources\BookingResource;
use App\Http\Resources\FieldResource;
use App\Services\BookingService;
use App\Services\FieldService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Http\Resources\UserResource;

class AdminController extends Controller
{
    protected BookingService $bookingService;
    protected FieldService $fieldService;

    public function __construct(BookingService $bookingService, FieldService $fieldService)
    {
        $this->bookingService = $bookingService;
        $this->fieldService = $fieldService;
    }

    public function indexUsers(Request $request)
    {
        $query = User::with('roles');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('user_number', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = $request->query('per_page', 10);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return UserResource::collection($users);
    }

    public function getStats(): JsonResponse
    {
        $todayBookings = Booking::whereDate('created_at', Carbon::today())->count();
        $yesterdayBookings = Booking::whereDate('created_at', Carbon::yesterday())->count();
        
        $bookingTrend = 0;
        if ($yesterdayBookings > 0) {
            $bookingTrend = round((($todayBookings - $yesterdayBookings) / $yesterdayBookings) * 100, 1);
        }

        $pendingVerifications = Booking::where('status', 'pending')->count();
        $verificationBadge = $pendingVerifications > 0 ? 'Penting' : 'Selesai';

        $activeFields = Field::where('status', 'available')->count();

        $currentMonthRevenue = Booking::where('status', 'approved')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_price');

        $lastMonth = Carbon::now()->subMonth();
        $lastMonthRevenue = Booking::where('status', 'approved')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('total_price');

        $revenueTrend = 0;
        if ($lastMonthRevenue > 0) {
            $revenueTrend = round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => [
                'today_bookings' => [
                    'value' => $todayBookings,
                    'trend_percentage' => $bookingTrend,
                    'label' => 'Total Booking Hari Ini'
                ],
                'pending_verifications' => [
                    'value' => $pendingVerifications,
                    'badge' => $verificationBadge,
                    'label' => 'Menunggu Verifikasi'
                ],
                'active_fields' => [
                    'value' => $activeFields,
                    'badge' => 'Aktif',
                    'label' => 'Lapangan Aktif'
                ],
                'monthly_revenue' => [
                    'value' => (int) $currentMonthRevenue,
                    'trend_percentage' => $revenueTrend,
                    'label' => 'Pendapatan Bulan Ini'
                ]
            ]
        ]);
    }

    public function indexBookings(Request $request)
    {
        $filters = $request->only(['status', 'booking_type', 'user_id', 'field_id', 'date', 'search']);
        $perPage = $request->query('per_page', 10);

        $bookings = $this->bookingService->getAllBookings($filters, $perPage);

        return BookingResource::collection($bookings);
    }

    public function approveBooking($id)
    {
        $booking = Booking::find($id);

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

    public function rejectBooking($id)
    {
        $booking = Booking::find($id);

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

    public function attendBooking($id)
    {
        $booking = Booking::find($id);

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

    public function updateUserStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,suspended',
        ]);

        $user = User::findOrFail($id);
        
        if ($user->hasRole('admin')) {
            return response()->json([
                'message' => 'Tidak dapat mengubah status akun sesama Admin.'
            ], 403);
        }

        $user->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status user berhasil diperbarui menjadi ' . $request->status,
            'user' => $user
        ]);
    }

    public function storeField(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'status' => 'required|in:available,maintenance',
            'surface_type' => 'nullable|in:vinyl,parket,semen',
            'image_file' => 'nullable|image|max:2048',
        ]);

        $field = $this->fieldService->createField($validatedData);
        return new FieldResource($field);
    }

    public function updateField(Request $request, $id)
    {
        $field = Field::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'status' => 'nullable|in:available,maintenance',
            'surface_type' => 'nullable|in:vinyl,parket,semen',
            'image_file' => 'nullable|image|max:2048',
        ]);

        $updatedField = $this->fieldService->updateField($field, $validatedData);
        return new FieldResource($updatedField);
    }

    public function destroyField($id)
    {
        $field = Field::findOrFail($id);
        $this->fieldService->deleteField($field);

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas lapangan berhasil dihapus.'
        ]);
    }
}
