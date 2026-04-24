<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = \App\Models\User::create([
            'name' => 'pulung',
            'email' => 'admin@admin.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'phone' => '081234567890',
            'nim' => null,
        ]);
        $admin->assignRole('admin');

        $umum = \App\Models\User::create([
            'name' => 'fajar',
            'email' => 'umum@umum.com',
            'password' => \Illuminate\Support\Facades\Hash::make('umum'),
            'phone' => '081234567891',
            'nim' => null,
        ]);
        $umum->assignRole('umum');

        $mahasiswa = \App\Models\User::create([
            'name' => 'aprilian',
            'email' => 'mhs@mhs.com',
            'password' => \Illuminate\Support\Facades\Hash::make('mhs'),
            'phone' => '081298765432',
            'nim' => 'A11.2023.01042',
        ]);
        $mahasiswa->assignRole('mahasiswa');
    }
}
