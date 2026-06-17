<?php

namespace Database\Seeders;

use App\Models\Field;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    protected array $specsByCategory = [
        'Futsal' => [
            ['label' => 'Ukuran Lapangan', 'value' => '25m × 16m (Standar FIFA)'],
            ['label' => 'Pencahayaan', 'value' => 'LED 500 Lux Anti-Silau'],
            ['label' => 'Kapasitas Pemain', 'value' => '5 vs 5 (10 pemain)'],
            ['label' => 'Fasilitas', 'value' => 'Bola, Cone, Rompi Latihan'],
        ],
        'Basket' => [
            ['label' => 'Ukuran Lapangan', 'value' => '28m × 15m (Standar FIBA)'],
            ['label' => 'Pencahayaan', 'value' => 'LED 750 Lux Merata'],
            ['label' => 'Kapasitas Pemain', 'value' => '5 vs 5 (10 pemain)'],
            ['label' => 'Fasilitas', 'value' => 'Bola Basket, Scoreboard Digital'],
        ],
        'Badminton' => [
            ['label' => 'Ukuran Lapangan', 'value' => '13.4m × 6.1m (Standar BWF)'],
            ['label' => 'Pencahayaan', 'value' => 'LED 400 Lux Anti-Silau'],
            ['label' => 'Tinggi Net', 'value' => '1.55m (Standar)'],
            ['label' => 'Fasilitas', 'value' => 'Shuttlecock Sewa, Raket Sewa'],
        ],
        'Tennis' => [
            ['label' => 'Ukuran Lapangan', 'value' => '23.77m × 10.97m (Standar ITF)'],
            ['label' => 'Pencahayaan', 'value' => 'LED 600 Lux'],
            ['label' => 'Kapasitas Penonton', 'value' => '50 kursi tribun'],
            ['label' => 'Fasilitas', 'value' => 'Bola Sewa, Raket Sewa'],
        ],
        'Voli' => [
            ['label' => 'Ukuran Lapangan', 'value' => '18m × 9m (Standar FIVB)'],
            ['label' => 'Pencahayaan', 'value' => 'LED 500 Lux'],
            ['label' => 'Tinggi Net', 'value' => '2.43m / 2.24m (Putra/Putri)'],
            ['label' => 'Fasilitas', 'value' => 'Bola Voli, Sound System'],
        ],
    ];

    public function run(): void
    {
        $base = 'https://ruvikuwgtggtcmksafts.supabase.co/storage/v1/object/public/Field-Image';

        $fields = [
            [
                'name' => 'Lapangan Futsal Internasional A',
                'description' => 'Lapangan futsal standar internasional dengan karpet vinyl sintetis premium dan ventilasi modern.',
                'surface_type' => 'vinyl',
                'rating' => 4.8,
                'image_url' => "$base/Main%20Futsal.png",
                'category' => 'Futsal',
                'status' => 'available',
                'carousel_urls' => [
                    "$base/Carousel%201%20Futsal.png",
                    "$base/Carousel%202%20Futsal.png",
                    "$base/Carousel%203%20Futsal.png",
                    "$base/Carousel%204%20Futsal.png",
                ],
            ],
            [
                'name' => 'Arena Basket Indoor B',
                'description' => 'Lapangan basket indoor berkualitas tinggi dengan lantai kayu parket impor standar NBA.',
                'surface_type' => 'parket',
                'rating' => 4.9,
                'image_url' => "$base/Main%20Baskett.webp",
                'category' => 'Basket',
                'status' => 'available',
                'carousel_urls' => [
                    "$base/Carousel%201%20Baskett.webp",
                    "$base/Carousel%202%20Baskett.png.webp",
                    "$base/Carousel%203%20Baskett.png.webp",
                    "$base/Carousel%204%20Baskett.png.webp",
                ],
            ],
            [
                'name' => 'Court Badminton Garuda',
                'description' => 'Lapangan badminton indoor karpet vinyl hijau profesional dengan pencahayaan LED anti-silau.',
                'surface_type' => 'vinyl',
                'rating' => 4.7,
                'image_url' => "$base/Main%20Badminton.webp",
                'category' => 'Badminton',
                'status' => 'available',
                'carousel_urls' => [
                    "$base/Carousel%201%20Badminton.webp",
                    "$base/Carousel%202%20Badminton.webp",
                    "$base/Carousel%203%20Badminton.webp",
                    "$base/Carousel%204%20Badminton.webp",
                ],
            ],
            [
                'name' => 'Court Tennis Green Wave',
                'description' => 'Lapangan tennis hard court outdoor dengan permukaan semen halus berkualitas tinggi.',
                'surface_type' => 'semen',
                'rating' => 4.6,
                'image_url' => "$base/Main%20Tennis.webp",
                'category' => 'Tennis',
                'status' => 'available',
                'carousel_urls' => [
                    "$base/Carousel%201%20Tennis.webp",
                    "$base/Carousel%202%20Tennis.webp",
                    "$base/Carousel%203%20Tennis.webp",
                    "$base/Carousel%204%20Tennis.webp",
                ],
            ],
            [
                'name' => 'Arena Voli Thunder Dome',
                'description' => 'Lapangan voli indoor lantai vinyl empuk dengan net standar internasional.',
                'surface_type' => 'vinyl',
                'rating' => 4.5,
                'image_url' => "$base/Main%20Volley.webp",
                'category' => 'Voli',
                'status' => 'maintenance',
                'carousel_urls' => [
                    "$base/Carousel%201%20Volley.webp",
                    "$base/Carousel%202%20Volley.webp",
                    "$base/Carousel%203%20Volley.webp",
                    "$base/Carousel%204%20Volley.webp",
                ],
            ],
        ];

        foreach ($fields as $fieldData) {
            $category = $fieldData['category'];
            $specifications = $this->specsByCategory[$category] ?? [];

            $field = Field::create([
                'name' => $fieldData['name'],
                'image_url' => $fieldData['image_url'],
                'category' => $fieldData['category'],
            ]);

            $field->detail()->create([
                'description' => $fieldData['description'],
                'surface_type' => $fieldData['surface_type'],
                'rating' => $fieldData['rating'],
                'status' => $fieldData['status'],
                'carousel_urls' => $fieldData['carousel_urls'] ?? null,
                'specifications' => $specifications,
            ]);
        }
    }
}
