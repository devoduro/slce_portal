<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Programme;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create courses with Ghanaian university context
        $courses = [
            // Computer Science Courses
           
            
            
            [
                'code' => 'EBC111',
                'title' => 'FOUNDATIONS OF EDUCATION IN GHANA',
                'credit_hours' => 3,

                'programme_id' => 1, // Available to all programmes
                'semester_id' => 1, // Second Semester
                'is_core' => false,
                'description' => '',
            ],
            [
                'code' => 'EBC121',
                'title' => 'INCLUSIVE SCHOOL-BASED INQUIRY',
                'credit_hours' => 3,

                'programme_id' => 2, // Available to all programmes
                'semester_id'    => 1, // Second Semester
                'is_core' => false,
                'description' => '',
            ],
            [
                'code' => 'EBC122',
                'title' => 'LEARNING, TEACHING AND APPLYING GEOMETRY',
                'credit_hours' => 3,

                'programme_id' => 2, // Available to all programmes
                'semester_id' => 1, // Seco1nd Semester
                'is_core' => false,
                'description' => '',
            ],
            [
                'code' => 'EBC123',
                'title' => 'INTRODUCTION TO ICT',
                'credit_hours' => 3,

                'programme_id' => 1, // Available to all programmes
                'semester_id' => 2, // Second Semester
                'is_core' => false,
                'description' => '',
            ],
        ];

        foreach ($courses as $courseData) {
            $programmeId = $courseData['programme_id'];
            unset($courseData['programme_id']);
            
            $course = Course::firstOrCreate(
                ['code' => $courseData['code']],
                $courseData
            );
            
            $programme = Programme::find($programmeId);
            if ($programme) {
                $course->programmes()->syncWithoutDetaching([$programmeId]);
            }
        }
    }
}
