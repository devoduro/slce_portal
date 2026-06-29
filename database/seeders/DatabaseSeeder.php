<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the AdminUserSeeder to create an admin user
        $this->call([
            AdminUserSeeder::class,
            ProgrammeSeeder::class,
            AcademicYearSeeder::class,
            SemesterSeeder::class,
            CourseSeeder::class,
            GradeSchemeSeeder::class,
            ClassificationSeeder::class,
            StudentSeeder::class,
            UserSeeder::class,
            ResultSeeder::class,
            SettingsTableSeeder::class,
        ]);
        
        // Create a test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'staff',
        ]);
    }
}
