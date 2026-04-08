<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminPassword = env('ADMIN_PASSWORD', 'Admin@123');
        $adminUsername = trim((string) env('ADMIN_USERNAME', env('ADMIN_EID', 'ADM0001')));
        $now = now();

        DB::table('admins')->upsert(
            [[
                'name' => env('ADMIN_NAME', 'System Admin'),
                'username' => $adminUsername,
                'password' => Hash::make($adminPassword),
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['username'],
            ['name', 'password', 'updated_at']
        );

        $this->command?->info('Admin account is ready in admins table: ' . $adminUsername);
    }
}