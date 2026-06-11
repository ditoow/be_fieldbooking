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

        $activeFields = Field::whereHas('detail', fn($q) => $q->where('status', 'available'))->count();

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
            'total_users' => \App\Models\User::count(),
            'total_fields' => Field::count(),
            'total_bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'data' => [
                'total_users' => \App\Models\User::count(),
                'total_fields' => Field::count(),
                'total_bookings' => Booking::count(),
                'pending_bookings' => Booking::where('status', 'pending')->count(),
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

    public function getRevenueTrend(): JsonResponse
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        
        $days = [
            'SENIN' => 0,
            'SELASA' => 0,
            'RABU' => 0,
            'KAMIS' => 0,
            'JUMAT' => 0,
            'SABTU' => 0,
            'MINGGU' => 0,
        ];

        $bookings = Booking::where('status', 'approved')
            ->whereBetween('created_at', [$startOfWeek, Carbon::now()->endOfWeek()])
            ->get();

        $dayMap = [
            1 => 'SENIN',
            2 => 'SELASA',
            3 => 'RABU',
            4 => 'KAMIS',
            5 => 'JUMAT',
            6 => 'SABTU',
            0 => 'MINGGU',
        ];

        foreach ($bookings as $booking) {
            $dayOfWeek = $booking->created_at->dayOfWeek;
            $dayName = $dayMap[$dayOfWeek] ?? null;
            if ($dayName && isset($days[$dayName])) {
                $days[$dayName] += $booking->total_price;
            }
        }

        $formattedData = [];
        foreach ($days as $name => $total) {
            $formattedData[] = [
                'name' => $name,
                'realisasi' => $total,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Revenue trend retrieved successfully',
            'data' => $formattedData,
        ]);
    }
}
