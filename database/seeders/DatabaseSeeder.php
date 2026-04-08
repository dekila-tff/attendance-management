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

        // Employee accounts are self-registered from the app.
        // Run EmployeeSeeder manually only when a CSV import is intentionally needed.
        // $this->call(EmployeeSeeder::class);
    }
}
