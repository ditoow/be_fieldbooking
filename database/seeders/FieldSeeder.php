<?php

namespace Database\Seeders;

use App\Models\Field;
use App\Models\Schedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            [
                'name' => 'Arena Futsal Emerald',
                'description' => 'Lapangan futsal rumput sintetis premium dengan pencahayaan LED spektakuler.',
                'category' => 'Futsal',
                'status' => 'available',
            ],
            [
                'name' => 'Court Badminton Garuda',
                'description' => 'Lapangan badminton indoor dengan lantai parket import dan sistem ventilasi modern.',
                'category' => 'Badminton',
                'status' => 'available',
            ],
            [
                'name' => 'Lapangan Basket Red Fire',
                'description' => 'Lapangan basket indoor standar NBA dengan lantai maple berkualitas tinggi.',
                'category' => 'Basket',
                'status' => 'available',
            ],
            [
                'name' => 'Arena Voli Thunder Dome',
                'description' => 'Lapangan voli indoor dengan lantai vinyl dan pencahayaan arena profesional.',
                'category' => 'Voli',
                'status' => 'available',
            ],
            [
                'name' => 'Court Tennis Green Wave',
                'description' => 'Lapangan tennis hard court dengan permukaan acrylic dan net standar internasional.',
                'category' => 'Tennis',
                'status' => 'available',
            ],
        ];

        foreach ($fields as $fieldData) {
            $field = Field::create($fieldData);

            for ($dayOffset = 0; $dayOffset < 30; $dayOffset++) {
                $date = now()->addDays($dayOffset)->format('Y-m-d');

                for ($hour = 6; $hour < 24; $hour++) {
                    $price = ($hour >= 16) ? 50000 : 40000;

                    Schedule::create([
                        'field_id' => $field->id,
                        'date' => $date,
                        'start_time' => sprintf('%02d:00', $hour),
                        'end_time' => sprintf('%02d:00', $hour + 1),
                        'price' => $price,
                        'status' => 'available',
                    ]);
                }
            }
        }
    }
}
