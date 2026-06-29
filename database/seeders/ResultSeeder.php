<?php

namespace Database\Seeders;

use App\Models\Result;
use Illuminate\Database\Seeder;

class ResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create results for students with realistic scores
        $results = [
            // Computer Science Student 1 (Kwame Addo) - First Semester
           
        ];

        foreach ($results as $result) {
            Result::firstOrCreate(
                [
                    'student_id' => $result['student_id'],
                    'course_id' => $result['course_id'],
                    'academic_year_id' => $result['academic_year_id'],
                    'semester_id' => $result['semester_id']
                ],
                $result
            );
        }
    }
}
