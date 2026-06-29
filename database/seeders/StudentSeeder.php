<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create students with Ghanaian names and context
        $students = [
            [
                'index_number' => '525SLEG001',
                'full_name' => 'Kwesi Mensah',
                'date_of_birth' => '2000-05-15',
                'gender' => 'Male',
                'programme_id' => 1, // BSc Computer Science
                'email' => 'kmensah@student.example.com',
                'phone' => '+233 24 123 4567',
            ],
        ];

        foreach ($students as $student) {
            Student::firstOrCreate(
                ['index_number' => $student['index_number']],
                $student
            );
        }
    }
}
