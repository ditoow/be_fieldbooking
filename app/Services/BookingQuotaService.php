<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BookingQuotaService
{
    public const WEEKLY_LIMIT = 10;

    /**
     * Assert that the user is within their weekly booking quota.
     *
     * @param User $user
     * @param int $additionalHours
     * @throws ValidationException
     */
    public function assertWithinQuota(User $user, int $additionalHours): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        $used = $this->getWeeklyUsage($user);
        if ($used + $additionalHours > self::WEEKLY_LIMIT) {
            throw ValidationException::withMessages([
                'time_slots' => "Total booking Anda minggu ini {$used} jam, " .
                                "tidak dapat menambah {$additionalHours} jam lagi. " .
                                "Batas maksimal " . self::WEEKLY_LIMIT . " jam/minggu.",
            ]);
        }
    }

    /**
     * Get the total hours booked by the user in the current calendar week (Asia/Jakarta).
     *
     * @param User $user
     * @return int
     */
    public function getWeeklyUsage(User $user): int
    {
        $start = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        $end = Carbon::now('Asia/Jakarta')->endOfWeek(Carbon::SUNDAY);

        return (int) DB::table('booking_details as bd')
            ->join('bookings as b', 'b.id', '=', 'bd.booking_id')
            ->join('schedules as s', 's.id', '=', 'bd.schedule_id')
            ->where('b.user_id', $user->id)
            ->whereIn('b.status', ['pending', 'approved'])
            ->whereBetween('s.date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->count();
    }
}
