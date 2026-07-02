<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create staff users with Ghanaian context
        $users = [
            [
                'name' => 'Kwame Nkrumah',
                'email' => 'knkrumah@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            
       
            [
                'name' => 'Ama Ata Aidoo',
                'email' => 'aaidoo@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $user['password'],
                    'role' => $user['role'],
                ]
            );
        }
        
        // Create student user accounts
        $students = \App\Models\Student::all();
        foreach ($students as $student) {
            try {
                User::firstOrCreate(
                    ['student_id' => $student->id],
                    [
                        'name' => $student->full_name,
                        'email' => $student->email,
                        'password' => Hash::make('password'),
                        'role' => 'student',
                        'first_login' => true,
                        'index_number' => $student->index_number,
                    ]
                );
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // A student's email/index_number sometimes collides with an existing account
                // (data-entry duplicate). Skip that one student rather than aborting the whole seed.
                \Illuminate\Support\Facades\Log::warning("Skipped creating user account for student {$student->id} ({$student->index_number}): {$e->getMessage()}");
            }
        }
    }
}
