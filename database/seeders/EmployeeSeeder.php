<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/data.csv');
        
        if (!File::exists($csvFile)) {
            $this->command->error('CSV file not found at: ' . $csvFile);
            return;
        }

        $file = fopen($csvFile, 'r');
        
        // Skip header row
        $header = fgetcsv($file);
        
        $count = 0;
        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            $eid = $row[0];
            $name = trim($row[1]);
            $designation = $row[2];
            $department = $row[3];
            $username = $row[4];
            $password = $row[5];
            $role = $row[6];
            $status = $row[7];

            // Create or update user
            User::updateOrCreate(
                ['eid' => $eid],
                [
                    'name' => $name,
                    'email' => $username . '@ntmh.bt', // Create email from username
                    'password' => Hash::make($password),
                    'designation' => $designation,
                    'department' => $department,
                    'role' => $role,
                    'status' => $status,
                ]
            );

            $count++;
        }

        fclose($file);

        $this->command->info("Successfully imported {$count} employees from CSV.");
    }
}
