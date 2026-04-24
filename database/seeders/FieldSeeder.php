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
            'nama_lapangan' => 'Lapangan Futsal A',
            'deskripsi' => 'Lapangan rumput sintetis standar internasional dengan pencahayaan spektakuler.',
            'kategori_lapangan' => 'Futsal',
            'status' => 'tersedia',
        ]);
    }
}
