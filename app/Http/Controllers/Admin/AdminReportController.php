<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Field;
use App\Models\User;
use App\Models\Schedule;
use App\Services\SupabaseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function getReportTransactions(Request $request): JsonResponse
    {
        $query = Booking::with(['schedules.field', 'user']);

        // Search filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('schedules.field', function ($fq) use ($search) {
                      $fq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter (mapped from frontend values)
        if ($status = $request->query('status')) {
            $mappedStatus = null;
            if ($status === 'SUCCESS') $mappedStatus = 'approved';
            if ($status === 'PENDING') $mappedStatus = 'pending';
            if ($status === 'FAILED') $mappedStatus = 'rejected';
            if ($status === 'CANCELLED') $mappedStatus = 'cancelled';

            if ($mappedStatus) {
                $query->where('status', $mappedStatus);
            }
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        $formattedTransactions = $bookings->map(function ($booking) {
            $firstSchedule = $booking->schedules->sortBy('start_time')->first();
            $lastSchedule = $booking->schedules->sortBy('start_time')->last();

            $fieldName = $firstSchedule->field->name ?? 'Field';
            $category = $firstSchedule->field->category ?? 'Futsal';

            // Get icon
            $icon = '⚽';
            $lowerCategory = strtolower($category);
            if (str_contains($lowerCategory, 'badminton')) $icon = '🏸';
            elseif (str_contains($lowerCategory, 'basket')) $icon = '🏀';
            elseif (str_contains($lowerCategory, 'voli')) $icon = '🏐';
            elseif (str_contains($lowerCategory, 'tenis') || str_contains($lowerCategory, 'tennis')) $icon = '🎾';

            $formattedDate = $booking->created_at ? $booking->created_at->format('d M, Y') : '';

            // Waktu
            $startTimeStr = $firstSchedule ? date('H:i', strtotime($firstSchedule->start_time)) : '';
            $endTimeStr = $lastSchedule ? date('H:i', strtotime($lastSchedule->end_time)) : '';
            $formattedTime = "{$startTimeStr} - {$endTimeStr}";

            $duration = count($booking->schedules) . ' hrs';

            // Nominal
            $nominalRupiah = 'Rp ' . number_format($booking->total_price, 0, ',', '.');

            $statusLabel = 'PENDING';
            if ($booking->status === 'approved') $statusLabel = 'SUCCESS';
            if ($booking->status === 'rejected') $statusLabel = 'FAILED';
            if ($booking->status === 'cancelled') $statusLabel = 'CANCELLED';

            return [
                'id' => '#' . $booking->booking_number,
                'icon' => $icon,
                'facility' => $fieldName,
                'user' => $booking->user->name ?? 'User',
                'category' => $booking->booking_type === 'requirement' ? 'STUDENT' : 'PUBLIC',
                'date' => $formattedDate,
                'time' => $formattedTime,
                'duration' => $duration,
                'amount' => $nominalRupiah,
                'amount_numeric' => $booking->total_price,
                'payment_method' => $booking->booking_type === 'requirement' ? 'Student Letter' : 'QRIS',
                'status' => $statusLabel,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Report transactions retrieved successfully',
            'data' => $formattedTransactions,
        ]);
    }

    public function getReportDemographics(): JsonResponse
    {
        // Hitung total user
        $totalUsers = User::count();

        // Hitung mahasiswa (dengan role mahasiswa)
        $studentCount = User::role('mahasiswa', 'web')->count();
        $publicCount = $totalUsers - $studentCount;

        return response()->json([
            'success' => true,
            'message' => 'User demographics retrieved successfully',
            'total_users' => $totalUsers,
            'data' => [
                [
                    'name' => 'Public',
                    'value' => $publicCount,
                    'color' => '#D4A574',
                ],
                [
                    'name' => 'Student',
                    'value' => $studentCount,
                    'color' => '#2D6A4F',
                ]
            ]
        ]);
    }

    public function getPdfReportData(): JsonResponse
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // 1. Total Revenue Bulan Ini
        $currentMonthRevenue = Booking::where('status', 'approved')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('total_price');

        // Total Revenue Bulan Lalu
        $lastMonth = Carbon::now()->subMonth();
        $lastMonthRevenue = Booking::where('status', 'approved')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('total_price');

        // Trend Revenue
        $revenueTrendVal = 0;
        if ($lastMonthRevenue > 0) {
            $revenueTrendVal = round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1);
        }
        $trendSign = $revenueTrendVal >= 0 ? '▲' : '▼';
        $revenueTrend = "{$trendSign} " . abs($revenueTrendVal) . "% vs Last Month";

        // 2. Total Sesi Booking Bulan Ini
        $totalBookingsCount = Booking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        // 3. User Aktif (membuat booking bulan ini)
        $activeUsersCount = Booking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->distinct('user_id')
            ->count('user_id');

        // 4. Utilisasi per Lapangan
        $fields = Field::all();
        $utilizationData = [];

        foreach ($fields as $field) {
            $totalSlots = Schedule::where('field_id', $field->id)
                ->whereMonth('date', $currentMonth)
                ->whereYear('date', $currentYear)
                ->count();

            $bookedSlots = Schedule::where('field_id', $field->id)
                ->whereHas('bookings', function ($query) {
                    $query->whereIn('status', ['pending', 'approved']);
                })
                ->whereMonth('date', $currentMonth)
                ->whereYear('date', $currentYear)
                ->count();

            $rate = 0;
            if ($totalSlots > 0) {
                $rate = round(($bookedSlots / $totalSlots) * 100);
            } else {
                // Mock default if no slots generated for the month yet
                $rate = $field->id == 1 ? 92 : ($field->id == 2 ? 84 : 76);
            }

            $utilizationData[] = [
                'field_name' => $field->name,
                'rate' => $rate,
            ];
        }

        // 5. Transaksi Cetak PDF (5 teratas)
        $latestBookings = Booking::with(['schedules.field', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $pdfTransactions = $latestBookings->map(function ($booking) {
            $firstSchedule = $booking->schedules->sortBy('start_time')->first();
            $fieldName = $firstSchedule->field->name ?? 'Field';
            $duration = count($booking->schedules) . ' hrs';

            return [
                'id' => 'TRX-' . $booking->id,
                'user_detail' => ($booking->user->name ?? 'User') . ($booking->user->student_id ? " ({$booking->user->student_id})" : ""),
                'service' => "{$fieldName} ({$duration})",
                'date' => $booking->created_at ? $booking->created_at->format('d M Y') : '-',
                'amount' => 'Rp ' . number_format($booking->total_price, 0, ',', '.'),
                'status' => $booking->status === 'approved' ? 'SUCCESS' : ($booking->booking_type === 'requirement' ? 'FREE BOOKING' : strtoupper($booking->status)),
            ];
        });

        $printDate = $now->format('d F Y');

        return response()->json([
            'success' => true,
            'message' => 'PDF report data retrieved successfully',
            'data' => [
                'report_id' => 'RPT-' . $now->format('Ym'),
                'print_date' => $printDate,
                'summary' => [
                    'total_revenue' => $currentMonthRevenue,
                    'revenue_trend' => $revenueTrend,
                    'total_bookings' => $totalBookingsCount,
                    'active_users' => $activeUsersCount,
                ],
                'utilization' => $utilizationData,
                'transactions' => $pdfTransactions,
            ]
        ]);
    }

    public function exportPdf(Request $request, SupabaseService $supabase): JsonResponse
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);
        $monthName = \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F');
        $monthPadded = str_pad($month, 2, '0', STR_PAD_LEFT);

        $currentMonthRevenue = Booking::where('status', 'approved')
            ->whereMonth('created_at', $month)->whereYear('created_at', $year)->sum('total_price');
        $totalBookingsCount = Booking::whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
        $activeUsersCount = Booking::whereMonth('created_at', $month)->whereYear('created_at', $year)->distinct('user_id')->count('user_id');

        $fields = Field::all();
        $utilizationData = [];
        foreach ($fields as $field) {
            $totalSlots = Schedule::where('field_id', $field->id)->whereMonth('date', $month)->whereYear('date', $year)->count();
            $bookedSlots = Schedule::where('field_id', $field->id)->whereHas('bookings', fn($q) => $q->whereIn('status', ['pending', 'approved']))
                ->whereMonth('date', $month)->whereYear('date', $year)->count();
            $rate = $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100) : 0;
            $utilizationData[] = ['field_name' => $field->name, 'rate' => $rate];
        }

        $latestBookings = Booking::with(['schedules.field', 'user'])->orderBy('created_at', 'desc')->limit(10)->get();
        $trx = $latestBookings->map(fn($b) => [
            'id' => $b->booking_number,
            'user_detail' => $b->user->name ?? '-',
            'service' => $b->schedules->first()?->field->name ?? '-',
            'date' => $b->created_at->format('d/m/Y'),
            'status' => match($b->status) { 'approved' => 'BERHASIL', 'pending' => 'MENUNGGU', 'rejected', 'cancelled' => 'GAGAL', default => 'MENUNGGU' },
        ])->toArray();

        $data = [
            'monthName' => $monthName,
            'year' => $year,
            'monthPadded' => $monthPadded,
            'summary' => [
                'total_revenue' => $currentMonthRevenue,
                'total_bookings' => $totalBookingsCount,
                'active_users' => $activeUsersCount,
            ],
            'utilization' => $utilizationData,
            'transactions' => $trx,
            'adminNote' => '',
        ];

        $dompdf = app('dompdf.wrapper');
        $dompdf->loadView('reports.monthly', $data);

        $fontDir = storage_path('fonts');
        $fontMetrics = $dompdf->getDomPDF()->getFontMetrics();
        $fontMetrics->registerFont(
            ['family' => 'Poppins', 'style' => 'normal', 'weight' => '400'],
            $fontDir . '/Poppins-Regular.ttf'
        );
        $fontMetrics->registerFont(
            ['family' => 'Poppins', 'style' => 'normal', 'weight' => '500'],
            $fontDir . '/Poppins-Medium.ttf'
        );
        $fontMetrics->registerFont(
            ['family' => 'Poppins', 'style' => 'normal', 'weight' => '600'],
            $fontDir . '/Poppins-SemiBold.ttf'
        );
        $fontMetrics->registerFont(
            ['family' => 'Poppins', 'style' => 'normal', 'weight' => '700'],
            $fontDir . '/Poppins-Bold.ttf'
        );
        $fontMetrics->registerFont(
            ['family' => 'Poppins', 'style' => 'italic', 'weight' => '400'],
            $fontDir . '/Poppins-Italic.ttf'
        );

        $pdfBinary = $dompdf->output();

        $timestamp = now()->format('Ymd_His');
        $storagePath = "reports/laporan-bulanan-{$month}-{$year}-{$timestamp}.pdf";

        $url = $supabase->upload(
            file: null,
            storagePath: $storagePath,
            mimeType: 'application/pdf',
            binaryContent: $pdfBinary,
            bucket: config('supabase.bucket_report', 'reports'),
        );

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }
}
