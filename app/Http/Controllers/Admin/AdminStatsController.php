<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Field;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AdminStatsController extends Controller
{
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
}
