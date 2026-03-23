<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@ntmh.bt');

        $adminUser = DB::table('users')
            ->where('email', $adminEmail)
            ->first();

        if (! $adminUser && Schema::hasColumn('users', 'is_admin')) {
            $adminUser = DB::table('users')
                ->where('is_admin', true)
                ->orderBy('id')
                ->first();
        }

        if (! $adminUser) {
            $this->command?->warn('No admin user found in users table.');
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
