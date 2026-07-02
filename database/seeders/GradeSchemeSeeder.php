<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\GradeScheme;
use Illuminate\Database\Seeder;

class GradeSchemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default grade scheme
        $gradeScheme = GradeScheme::firstOrCreate(
            ['name' => 'Ghanaian University Grading System'],
            [
                'description' => 'Standard grading system used in Ghanaian universities',
                'is_default' => true,
            ]
        );

        // Create grades for the scheme
        $grades = [
            [
                'grade' => 'A',
                'min_score' => 80.0,
                'gpa_value' => 4.0,
            ],
            [
                'grade' => 'B+',
                'min_score' => 75.0,
                'gpa_value' => 3.5,
            ],
            [
                'grade' => 'B',
                'min_score' => 70.0,
                'gpa_value' => 3.0,
            ],
            [
                'grade' => 'C+',
                'min_score' => 65.0,
                'gpa_value' => 2.5,
            ],
            [
                'grade' => 'C',
                'min_score' => 60.0,
                'gpa_value' => 2.0,
            ],
            [
                'grade' => 'D+',
                'min_score' => 55.0,
                'gpa_value' => 1.5,
            ],
            [
                'grade' => 'D',
                'min_score' => 50.0,
                'gpa_value' => 1.0,
            ],
            [
                'grade' => 'E',
                'min_score' => 45.0,
                'gpa_value' => 0.00,
            ],
            [
                'grade' => 'IC',
                'min_score' => 0.0,
                'gpa_value' => 0.0,
            ],
        ];

        // Add grades to the scheme
        foreach ($grades as $grade) {
            Grade::firstOrCreate(
                [
                    'grade_scheme_id' => $gradeScheme->id,
                    'grade' => $grade['grade'],
                ],
                [
                    'min_score' => $grade['min_score'],
                    'gpa_value' => $grade['gpa_value'],
                ]
            );
        }
    }
}
