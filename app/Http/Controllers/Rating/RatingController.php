<?php

namespace App\Http\Controllers\Rating;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Field;
use App\Models\Rating;
use App\Http\Requests\Rating\StoreRatingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RatingController extends Controller
{
    public function store(StoreRatingRequest $request, $bookingId)
    {
        $user = Auth::guard('api')->user();

        $booking = Booking::with('schedules')->where('user_id', $user->id)->findOrFail($bookingId);

        // Memastikan booking lunas/disetujui
        if ($booking->status !== 'approved') {
            return response()->json([
                'message' => 'Anda hanya dapat memberi rating pada pesanan yang sudah lunas/disetujui.'
            ], 422);
        }

        // Memastikan sudah selesai bermain (waktu sekarang > waktu selesai slot jadwal terakhir)
        $lastSchedule = $booking->schedules->sortByDesc(function ($schedule) {
            return $schedule->date . ' ' . $schedule->end_time;
        })->first();

        if ($lastSchedule) {
            $endDateTime = \Carbon\Carbon::parse($lastSchedule->date . ' ' . $lastSchedule->end_time);
            if (\Carbon\Carbon::now()->lt($endDateTime)) {
                return response()->json([
                    'message' => 'Anda baru dapat memberi rating setelah waktu bermain selesai.'
                ], 422);
            }
        } else {
            return response()->json([
                'message' => 'Pesanan tidak memiliki jadwal yang valid.'
            ], 422);
        }

        // Memastikan belum pernah memberi rating pada booking ini
        $exists = Rating::where('booking_id', $booking->id)->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Anda sudah memberikan rating untuk pesanan ini.'
            ], 422);
        }

        $validated = $request->validated();

        // Get field ID dari slot
        $fieldId = $lastSchedule->field_id;

        DB::transaction(function () use ($user, $booking, $fieldId, $validated) {
            Rating::create([
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'field_id' => $fieldId,
                'rating' => $validated['rating'],
                'review' => $validated['review'] ?? null,
            ]);

            // Hitung rata-rata rating baru untuk lapangan ini
            $average = Rating::where('field_id', $fieldId)->avg('rating');

            // Update ke detail_fields
            $field = Field::find($fieldId);
            if ($field && $field->detail) {
                $field->detail->update([
                    'rating' => round((float)$average, 1)
                ]);
            }
        });

        $user->notifications()
            ->whereRaw("data->>'booking_id' = ?", [(string) $booking->id])
            ->whereRaw("data->>'type' = ?", ['rating_reminder'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Rating dan ulasan Anda berhasil dikirim!'
        ]);
    }

    public function indexFieldRatings($fieldId)
    {
        $ratings = Rating::with('user:id,name')
            ->where('field_id', $fieldId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($ratings);
    }
}
