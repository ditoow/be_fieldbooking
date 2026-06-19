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
            'phone' => '+6281234567890',
            'student_id' => null,
        ]);
        $admin->assignRole('admin');

        // --- Umum Users ---

        $umumUsers = [
            ['name' => 'fajar', 'email' => 'umum@umum.com', 'password' => 'umumuser', 'phone' => '+6281234567891'],
            ['name' => 'budi santoso', 'email' => 'budi@gmail.com', 'password' => 'password', 'phone' => '+6281345678901'],
            ['name' => 'rina wati', 'email' => 'rina@gmail.com', 'password' => 'password', 'phone' => '+6281456789012'],
            ['name' => 'deni prasetyo', 'email' => 'deni@gmail.com', 'password' => 'password', 'phone' => '+6281567890123'],
        ];

        foreach ($umumUsers as $data) {
            $user = \App\Models\User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                'phone' => $data['phone'],
                'student_id' => null,
            ]);
            $user->assignRole('umum');
            $user->update(['user_number' => \App\Models\User::generateUserNumber('umum')]);
        }

        // --- Mahasiswa Users ---

        $mahasiswaUsers = [
            ['name' => 'aprilian', 'email' => 'mhs@mhs.dinus.ac.id', 'password' => 'mahasiswa', 'phone' => '+6281298765432', 'student_id' => 'A11.2023.01042'],
            ['name' => 'aditya saputra', 'email' => 'aditya@mhs.dinus.ac.id', 'password' => 'password', 'phone' => '+6281678901234', 'student_id' => 'A11.2023.01050'],
            ['name' => 'sari dewi', 'email' => 'sari@mhs.dinus.ac.id', 'password' => 'password', 'phone' => '+6281789012345', 'student_id' => 'A11.2023.01055'],
            ['name' => 'rizky maulana', 'email' => 'rizky@mhs.dinus.ac.id', 'password' => 'password', 'phone' => '+6281890123456', 'student_id' => 'A11.2023.01063'],
        ];

        foreach ($mahasiswaUsers as $data) {
            $user = \App\Models\User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                'phone' => $data['phone'],
                'student_id' => $data['student_id'],
            ]);
            $user->assignRole('mahasiswa');
            $user->update(['user_number' => \App\Models\User::generateUserNumber('mahasiswa')]);
        }
    }
}
