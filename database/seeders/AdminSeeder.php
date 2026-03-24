<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@ntmh.bt');
        $adminPassword = env('ADMIN_PASSWORD', 'Admin@123');

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => env('ADMIN_NAME', 'System Admin'),
                'password' => Hash::make($adminPassword),
                'eid' => env('ADMIN_EID', 'ADM0001'),
                'designation' => 'System Administrator',
                'department' => 'Administration',
                'role_id' => 3,
                'status' => 'Active',
            ]
        );

        $this->command?->info('Admin user is ready: ' . $adminEmail);
    }
}