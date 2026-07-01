<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Sentinel',
            'email' => 'admin@sentinel.local',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
        ]);
    }
}
