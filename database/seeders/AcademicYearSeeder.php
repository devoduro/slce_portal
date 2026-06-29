<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create academic years with Ghanaian academic calendar
        $academicYears = [
            [
                'name' => '2023/2024',
                'start_date' => '2023-09-01',
                'end_date' => '2024-07-31',
                'is_current' => true,
            ],
            [
                'name' => '2022/2023',
                'start_date' => '2022-09-01',
                'end_date' => '2023-07-31',
                'is_current' => false,
            ],
            [
                'name' => '2021/2022',
                'start_date' => '2021-09-01',
                'end_date' => '2022-07-31',
                'is_current' => false,
            ],
            [
                'name' => '2020/2021',
                'start_date' => '2020-09-01',
                'end_date' => '2021-07-31',
                'is_current' => false,
            ],
        ];

        foreach ($academicYears as $academicYear) {
            AcademicYear::firstOrCreate(
                ['name' => $academicYear['name']],
                $academicYear
            );
        }
    }
}
