<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'user_id' => 'ADM001',
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Admin',
            'password' => Hash::make('admin123'),
            'status' => 1,
        ]);
    }
}