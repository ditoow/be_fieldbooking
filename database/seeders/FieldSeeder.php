<?php

namespace Database\Seeders;

use App\Models\Field;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            [
                'name' => 'Lapangan Futsal Internasional A',
                'description' => 'Lapangan futsal standar internasional dengan karpet vinyl sintetis premium dan ventilasi modern.',
                'surface_type' => 'vinyl',
                'rating' => 4.8,
                'image_url' => 'https://res.cloudinary.com/dawn0omj0/image/upload/v1780116902/Futsal_-_Sintesis_pkw2kf.png',
                'category' => 'Futsal',
                'status' => 'available',
            ],
            [
                'name' => 'Arena Basket Indoor B',
                'description' => 'Lapangan basket indoor berkualitas tinggi dengan lantai kayu parket impor standar NBA.',
                'surface_type' => 'parket',
                'rating' => 4.9,
                'image_url' => 'https://res.cloudinary.com/dawn0omj0/image/upload/v1780116900/Basket_-_Kayu_dqctid.jpg',
                'category' => 'Basket',
                'status' => 'available',
            ],
            [
                'name' => 'Court Badminton Garuda',
                'description' => 'Lapangan badminton indoor karpet vinyl hijau profesional dengan pencahayaan LED anti-silau.',
                'surface_type' => 'vinyl',
                'rating' => 4.7,
                'image_url' => 'https://res.cloudinary.com/dawn0omj0/image/upload/v1780116902/Badminton_-_Vinyl_cehb7c.png',
                'category' => 'Badminton',
                'status' => 'available',
            ],
            [
                'name' => 'Court Tennis Green Wave',
                'description' => 'Lapangan tennis hard court outdoor dengan permukaan semen halus berkualitas tinggi.',
                'surface_type' => 'semen',
                'rating' => 4.6,
                'image_url' => 'https://res.cloudinary.com/dawn0omj0/image/upload/v1780116902/Tennis_-_Semen_Hard_Court_zdyt3r.jpg',
                'category' => 'Tennis',
                'status' => 'available',
            ],
            [
                'name' => 'Arena Voli Thunder Dome',
                'description' => 'Lapangan voli indoor lantai vinyl empuk dengan net standar internasional.',
                'surface_type' => 'vinyl',
                'rating' => 4.5,
                'image_url' => 'https://res.cloudinary.com/dawn0omj0/image/upload/v1780116900/Voli_-_Vinyl_bahpvn.jpg',
                'category' => 'Voli',
                'status' => 'maintenance',
            ],
        ];

        foreach ($fields as $fieldData) {
            Field::create($fieldData);
        }
    }
}
