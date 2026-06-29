<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create semesters with Ghanaian university context
        $semesters = [
            [
                'academic_year_id' => 1, // 2023/2024
                'name' => 'First Semester',
                'semester_number' => 1,
                'start_date' => '2024-11-01',
                'end_date' => '2025-05-30',
                'is_current' => true,
            ],
            [
                'academic_year_id' => 1, // 2023/2024
                'name' => 'Second Semester',
                'semester_number' => 2,
                'start_date' => '2025-06-01',
                'end_date' => '2025-10-30',
                'is_current' => false,
            ],
           
        ];

        foreach ($semesters as $semester) {
            Semester::firstOrCreate(
                [
                    'name' => $semester['name'],
                    'academic_year_id' => $semester['academic_year_id']
                ],
                $semester
            );
        }
    }
}
