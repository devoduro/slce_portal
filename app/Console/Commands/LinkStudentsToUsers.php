<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class LinkStudentsToUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:link-users {--create-missing : Create user accounts for students without one}'
                          . ' {--default-password= : Default password for new user accounts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link students to user accounts based on email and update index numbers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to link students with user accounts...');
        
        $createMissing = $this->option('create-missing');
        $defaultPassword = $this->option('default-password') ?: 'password123';
        
        $students = Student::all();
        $linkedCount = 0;
        $createdCount = 0;
        
        foreach ($students as $student) {
            // Skip students without email
            if (empty($student->email)) {
                $this->warn("Student ID {$student->id} has no email address. Skipping.");
                continue;
            }
            
            // Try to find user by email
            $user = User::where('email', $student->email)->first();
            
            if ($user) {
                // Update existing user
                $user->student_id = $student->id;
                $user->index_number = $student->enrollment_code;
                $user->save();
                
                $this->info("Linked student ID {$student->id} with user ID {$user->id}");
                $linkedCount++;
            } elseif ($createMissing) {
                // Create new user for student
                $user = new User();
                $user->name = $student->fathers_name ?? $student->mothers_name ?? 'Student ' . $student->id;
                $user->email = $student->email;
                $user->password = Hash::make($defaultPassword);
                $user->role = 'student';
                $user->student_id = $student->id;
                $user->index_number = $student->enrollment_code;
                $user->save();
                
                $this->info("Created new user for student ID {$student->id}");
                $createdCount++;
            } else {
                $this->warn("No user found for student ID {$student->id} with email {$student->email}");
            }
        }
        
        $this->info("Completed! Linked {$linkedCount} students with existing users.");
        
        if ($createMissing) {
            $this->info("Created {$createdCount} new user accounts for students.");
            if ($createdCount > 0) {
                $this->info("Default password for new accounts: {$defaultPassword}");
            }
        }
        
        return 0;
    }
}
