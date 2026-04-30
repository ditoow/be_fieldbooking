<?php

namespace Database\Seeders;

use App\Models\Field;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Field::create([
            'name' => 'Lapangan Futsal A',
            'description' => 'Lapangan rumput sintetis standar internasional dengan pencahayaan spektakuler.',
            'category' => 'Futsal',
            'status' => 'available',
        ]);
    }
}
