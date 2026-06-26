<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Rating;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatingSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            'Lapangan bagus, bersih, dan nyaman. Recommended!',
            'Fasilitas lengkap, cocok untuk latihan rutin.',
            'Pencahayaan cukup terang, sayang AC nya kurang dingin.',
            'Tempatnya strategis, gampang diakses. Mantap!',
            'Lapangan standar internasional, puas banget main di sini.',
            'Parkir luas, staff ramah, pengalaman booking mudah.',
            'Harga terjangkau untuk kualitas lapangan sebagus ini.',
            'Sayang jadwalnya sering penuh, harus booking jauh2 hari.',
        ];

        $approved = Booking::where('status', 'approved')->limit(50)->get();

        if ($approved->count() < 50) {
            $this->command->info('Tidak cukup booking approved untuk membuat 50 rating — RatingSeeder dilewati.');
            return;
        }

        DB::transaction(function () use ($approved, $reviews) {
            foreach ($approved as $i => $booking) {
                $schedule = $booking->schedules->first();
                if (!$schedule) continue;

                Rating::create([
                    'user_id' => $booking->user_id,
                    'booking_id' => $booking->id,
                    'field_id' => $schedule->field_id,
                    'rating' => rand(3, 5),
                    'review' => $reviews[$i % count($reviews)],
                ]);
            }

            $averages = Rating::select('field_id', DB::raw('ROUND(AVG(rating), 1) as avg_rating'))
                ->groupBy('field_id')
                ->get();

            foreach ($averages as $avg) {
                $field = \App\Models\Field::find($avg->field_id);
                if ($field && $field->detail) {
                    $field->detail->update(['rating' => $avg->avg_rating]);
                }
            }
        });

        $this->command->info('RatingSeeder: ' . $approved->count() . ' rating berhasil dibuat.');
    }
}
