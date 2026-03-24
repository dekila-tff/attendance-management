<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@ntmh.bt');
        $adminUsername = trim((string) env('ADMIN_USERNAME', ''));

        $adminUser = DB::table('users')
            ->where('email', $adminEmail)
            ->first();

        if (! $adminUser && $adminUsername !== '') {
            $adminUser = DB::table('users')
                ->where('eid', $adminUsername)
                ->orWhere('email', $adminUsername)
                ->first();
        }

        if (! $adminUser) {
            $this->command?->warn('No matching admin user found. Set ADMIN_EMAIL or ADMIN_USERNAME to an existing user.');
            return;
        }

        DB::table('admins')->truncate();

        DB::table('admins')->insert([
            'name' => $adminUser->name,
            'username' => $adminUser->eid ?? env('ADMIN_USERNAME', 'admin'),
            'password' => $adminUser->password ?? Hash::make(env('ADMIN_PASSWORD', 'Admin@123')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command?->info('Admins table updated with username: ' . ($adminUser->eid ?? env('ADMIN_USERNAME', 'admin')));
    }
}
