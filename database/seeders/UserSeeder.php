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
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        $mahasiswaRole = \App\Models\Role::where('name', 'mahasiswa')->first();

        if ($adminRole) {
            \App\Models\User::create([
                'role_id' => $adminRole->id,
                'name' => 'Admin Utama',
                'email' => 'admin@admin.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'phone' => '081234567890',
                'nim' => null,
            ]);
        }

        if ($mahasiswaRole) {
            \App\Models\User::create([
                'role_id' => $mahasiswaRole->id,
                'name' => 'Mahasiswa Test',
                'email' => 'mahasiswa@student.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'phone' => '081298765432',
                'nim' => 'A11.2023.12345',
            ]);
        }
    }
}
