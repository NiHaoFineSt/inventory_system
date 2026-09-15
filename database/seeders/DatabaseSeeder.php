<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // General Staff Test Account
        User::create([
            'name' => 'Staff User',
            'email' => 'staff@company.com',
            'password' => Hash::make('password123'),
            'role' => 'staff',
        ]);

        // Asset Team Test Account
        User::create([
            'name' => 'Asset Admin',
            'email' => 'admin@company.com',
            'password' => Hash::make('password123'),
            'role' => 'asset_team',
        ]);
    }
}