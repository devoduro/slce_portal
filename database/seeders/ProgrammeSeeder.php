<?php

namespace Database\Seeders;

use App\Models\Programme;
use Illuminate\Database\Seeder;

class ProgrammeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create programmes with Ghanaian university context
        $programmes = [
            [
                'name' => 'B.ED EARLY GRADE EDUCATION',
                'code' => 'EGE',
                'duration_years' => 4,
                'description' => 'EDUCATION',
                'department' => 'EDUCATION',
                'faculty' => 'EDUCATION',
            ],
            [
                'name' => 'B.ED UPPER PRIMARY EDUCATION',
                'code' => 'PE',
                'duration_years' => 4,
                'description' => 'EDUCATION',
                'department' => 'EDUCATION',
                'faculty' => 'EDUCATION',
            ],
            [
                'name' => 'B.ED JHS EDUCATION (LANGUAGES)',
                'code' => 'LE',
                'duration_years' => 4,
                'description' => 'EDUCATION',
                'department' => 'EDUCATION',
                'faculty' => 'EDUCATION',
            ],
            [
                'name' => 'B.ED JHS EDUCATION (HOME ECONOMICS)',
                'code' => 'HE',
                'duration_years' => 4,
                'description' => 'EDUCATION',
                'department' => 'EDUCATION',
                'faculty' => 'EDUCATION',
            ],
          
        ];

        foreach ($programmes as $programme) {
            Programme::firstOrCreate(
                ['code' => $programme['code']],
                $programme
            );
        }
    }
}
