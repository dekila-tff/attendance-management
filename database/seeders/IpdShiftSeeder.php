<?php

namespace Database\Seeders;

use App\Models\DepartmentShift;
use Illuminate\Database\Seeder;

class IpdShiftSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $department = 'IPD';

        $shifts = [
            [
                'name' => 'Morning',
                'start_time' => '08:00:00',
                'end_time' => '14:00:00',
                'on_time_until' => '08:30:00',
                'clock_out_after' => '14:00:00',
                'is_overnight' => false,
            ],
            [
                'name' => 'Evening',
                'start_time' => '14:00:00',
                'end_time' => '20:00:00',
                'on_time_until' => '14:30:00',
                'clock_out_after' => '20:00:00',
                'is_overnight' => false,
            ],
            [
                'name' => 'Night',
                'start_time' => '20:00:00',
                'end_time' => '08:00:00',
                'on_time_until' => '20:30:00',
                'clock_out_after' => '08:00:00',
                'is_overnight' => true,
            ],
        ];

        foreach ($shifts as $shift) {
            DepartmentShift::updateOrCreate(
                [
                    'department' => $department,
                    'name' => $shift['name'],
                ],
                $shift + ['department' => $department, 'is_active' => true]
            );
        }
    }
}
