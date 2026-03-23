<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(IpdShiftSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(AdminTableSeeder::class);

        // Import employees from CSV
        $this->call(EmployeeSeeder::class);
    }
}
