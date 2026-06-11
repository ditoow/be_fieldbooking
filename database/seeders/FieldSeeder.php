<?php

namespace Database\Seeders;

use App\Models\Field;
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
                'image_url' => 'https://qcizbglhafqgrphobbly.supabase.co/storage/v1/object/public/Field-Image/field-images/Futsal%20-%20Sintesis%20Small.png',
                'category' => 'Futsal',
                'status' => 'available',
            ],
            [
                'name' => 'Arena Basket Indoor B',
                'description' => 'Lapangan basket indoor berkualitas tinggi dengan lantai kayu parket impor standar NBA.',
                'surface_type' => 'parket',
                'rating' => 4.9,
                'image_url' => 'https://qcizbglhafqgrphobbly.supabase.co/storage/v1/object/public/Field-Image/field-images/Basket%20-%20Kayu%20Small.png',
                'category' => 'Basket',
                'status' => 'available',
            ],
            [
                'name' => 'Court Badminton Garuda',
                'description' => 'Lapangan badminton indoor karpet vinyl hijau profesional dengan pencahayaan LED anti-silau.',
                'surface_type' => 'vinyl',
                'rating' => 4.7,
                'image_url' => 'https://qcizbglhafqgrphobbly.supabase.co/storage/v1/object/public/Field-Image/field-images/Badminton%20-%20Vinyl%20Small.png',
                'category' => 'Badminton',
                'status' => 'available',
            ],
            [
                'name' => 'Court Tennis Green Wave',
                'description' => 'Lapangan tennis hard court outdoor dengan permukaan semen halus berkualitas tinggi.',
                'surface_type' => 'semen',
                'rating' => 4.6,
                'image_url' => 'https://qcizbglhafqgrphobbly.supabase.co/storage/v1/object/public/Field-Image/field-images/Tennis%20-%20Semen%20:%20Hard%20Court%20Small.png',
                'category' => 'Tennis',
                'status' => 'available',
            ],
            [
                'name' => 'Arena Voli Thunder Dome',
                'description' => 'Lapangan voli indoor lantai vinyl empuk dengan net standar internasional.',
                'surface_type' => 'vinyl',
                'rating' => 4.5,
                'image_url' => 'https://qcizbglhafqgrphobbly.supabase.co/storage/v1/object/public/Field-Image/field-images/Voli%20-%20Vinyl%20Small.png',
                'category' => 'Voli',
                'status' => 'maintenance',
            ],
        ];

        foreach ($fields as $fieldData) {
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
            ]);
        }
    }
}
