<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
                'designation' => 'Medical Superintendent',
                'department' => 'Administration',
                'role_id' => 1,
                'status' => 'Active',
            ]
        );

        if (Schema::hasColumn('users', 'is_admin')) {
            $admin->forceFill(['is_admin' => true])->save();
        }

        $this->command?->info('Admin user is ready: ' . $adminEmail);
    }
}