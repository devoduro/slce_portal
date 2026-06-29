<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogger;

class StudentAuthController extends Controller
{
    /**
     * Show the student login form.
     */
    public function showLoginForm()
    {
        return view('auth.student-login');
    }

    /**
     * Show the student change password form.
     */
    public function showChangePasswordForm()
    {
        if (auth()->user()->role !== User::ROLE_STUDENT) {
            return redirect()->route('login')->with('error', 'Access denied. Students only.');
        }
        return view('student.student_change-password');
    }

    /**
     * Handle student login request.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'index_number' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('student.login')
                ->withErrors($validator)
                ->withInput();
        }

        // Try to find student by index_number
        $student = Student::where('index_number', $request->index_number)->first();
        
        if (!$student) {
            return redirect()->route('student.login')
                ->withErrors(['index_number' => 'Invalid credentials'])
                ->withInput();
        }
        
        // Find user linked to this student
        $user = User::where('student_id', $student->id)->first();
        
        if (!$user) {
            return redirect()->route('student.login')
                ->withErrors(['index_number' => 'No user account found for this student'])
                ->withInput();
        }
        
        // Verify that the user has the student role
        if ($user->role !== User::ROLE_STUDENT) {
            return redirect()->route('student.login')
                ->withErrors(['index_number' => 'Invalid student account'])
                ->withInput();
        }

        // Attempt login with user credentials
        if (Auth::attempt(['email' => $user->email, 'password' => $request->password, 'role' => User::ROLE_STUDENT])) {
            $request->session()->regenerate();
            
            // Log the successful login
            app(ActivityLogger::class)->log('login', 'Student logged in successfully');
            
            // Set last activity timestamp
            $request->session()->put('last_activity', time());
            
            // If first login, redirect to password change
            if ($user->first_login) {
                return redirect()->route('student.change-password')
                    ->with('warning', 'Please change your password before continuing.');
            }
            
            // Otherwise redirect to dashboard
            return redirect()->route('student.dashboard');
        }

        return redirect()->route('student.login')
            ->withErrors(['index_number' => 'Invalid credentials'])
            ->withInput();
    }

    /**
     * Log the student out.
     */
    public function logout(Request $request)
    {
        // Log the logout activity before actually logging out
        if (Auth::check()) {
            app(ActivityLogger::class)->log('logout', 'Student logged out');
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('student.login')->with('message', 'You have been logged out successfully.');
    }

    /**
     * Show the student dashboard.
     */
    public function dashboard()
    {
        $student = Auth::user()->student;
        $results = $student->results()
            ->with(['course', 'semester', 'academicYear'])
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('semester_id', 'desc')
            ->get();

        // Calculate CGPA and its change from last semester
        $cgpa = $student->calculateCGPA();
        $lastSemesterResults = $results->where('semester_id', $results->first()?->semester_id - 1);
        $lastSemesterCGPA = $lastSemesterResults->isNotEmpty() 
            ? $student->calculateGPA($lastSemesterResults->first()->semester_id)
            : $cgpa;
        $cgpaChange = $cgpa - $lastSemesterCGPA;

        // Calculate total and remaining credits
        $totalCredits = $results->sum('course.credit_hours');
        $programmeCredits = $student->programme->total_credit_hours ?? 120; // Default to 120 if not set
        $remainingCredits = max(0, $programmeCredits - $totalCredits);

        // Get current semester courses count
        $currentSemester = \App\Models\Semester::where('is_current', true)->first();
        $currentCourses = $currentSemester 
            ? $student->results()
                ->where('semester_id', $currentSemester->id)
                ->count()
            : 0;

        // Get classification and other data
        $classification = $student->getClassification();

        return view('student.dashboard', compact(
            'student',
            'results',
            'cgpa',
            'cgpaChange',
            'classification',
            'totalCredits',
            'remainingCredits',
            'currentCourses',
            'currentSemester'
        ));
    }

    /**
     * Show the student's results.
     */
    public function results()
    {
        // Get the authenticated student
        $student = Auth::user()->student;
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Get all results with necessary relationships first
        $results = $student->results()
            ->with(['course', 'semester', 'academicYear'])
            ->join('academic_years', 'results.academic_year_id', '=', 'academic_years.id')
            ->orderByRaw('CAST(SUBSTRING_INDEX(academic_years.name, "/", 1) AS UNSIGNED) DESC')
            ->orderBy('semester_id')
            ->select('results.*')
            ->get();

        // Get academic years from results and sort them
        $academicYears = AcademicYear::whereHas('results', function($query) use ($student) {
            $query->where('student_id', $student->id);
        })->orderByRaw('CAST(SUBSTRING_INDEX(name, "/", 1) AS UNSIGNED) DESC')->get();
        
        // Initialize variables for GPA calculations
        $groupedResults = [];
        $yearGPAs = [];
        $cumulativeGPAs = [];
        $runningCreditPoints = 0;
        $runningCreditHours = 0;

        // Early return if no results
        if ($results->isEmpty()) {
            return view('student.results', compact('student', 'groupedResults', 'yearGPAs', 'cumulativeGPAs'));
        }
        
        // Initialize the structure for all academic years in order
        if ($academicYears->isNotEmpty()) {
            foreach ($academicYears as $academicYear) {
                if (!$academicYear) continue; // Skip if academic year is null
                
                $groupedResults[$academicYear->id] = [
                    'academic_year' => $academicYear,
                    'semesters' => [],
                    'year_credit_points' => 0,
                    'year_credit_hours' => 0,
                    'sort_key' => (int)explode('/', $academicYear->name)[0] // Add sort key
                ];
                
                // Initialize semesters that have results for this academic year
                $yearResults = $results->where('academic_year_id', $academicYear->id)->sortByDesc('semester_id');
                $yearSemesters = $yearResults->pluck('semester')->unique()->sortByDesc('id');
                
                foreach ($yearSemesters as $semester) {
                    if (!$semester) continue; // Skip if semester is null
                    
                    $groupedResults[$academicYear->id]['semesters'][$semester->id] = [
                        'semester' => $semester,
                        'results' => [],
                        'total_credit_points' => 0,
                        'total_credit_hours' => 0,
                        'gpa' => 0
                    ];
                }
            }
        }
        
        // Process all results
        foreach ($results as $result) {
            $academicYearId = $result->academic_year_id;
            $semesterId = $result->semester_id;
            
            if (!$result->course || !isset($result->grade_point)) continue; // Skip if course or grade is missing

            // Calculate credit points and hours
            $creditHours = $result->course->credit_hours ?? 0;
            if ($creditHours <= 0) continue; // Skip if invalid credit hours

            $creditPoints = $creditHours * ($result->grade_point ?? 0);
            
            // Add result to appropriate semester
            if (isset($groupedResults[$academicYearId]['semesters'][$semesterId])) {
                $groupedResults[$academicYearId]['semesters'][$semesterId]['results'][] = $result;
                
                // Update semester totals
                $groupedResults[$academicYearId]['semesters'][$semesterId]['total_credit_points'] += $creditPoints;
                $groupedResults[$academicYearId]['semesters'][$semesterId]['total_credit_hours'] += $creditHours;
                
                // Update year totals
                $groupedResults[$academicYearId]['year_credit_points'] += $creditPoints;
                $groupedResults[$academicYearId]['year_credit_hours'] += $creditHours;
                
                // Update running totals for CGPA
                $runningCreditPoints += $creditPoints;
                $runningCreditHours += $creditHours;
            }
        }

        // Convert to array and ensure it's sorted by academic year name
        $groupedResults = array_values(array_filter(collect($groupedResults)->sortByDesc(function($group) {
            return (int)explode('/', $group['academic_year']->name)[0];
        })->toArray()));
        
        // Calculate GPAs and sort results
        foreach ($groupedResults as $yearId => &$yearData) {
            // Calculate semester GPAs
            foreach ($yearData['semesters'] as &$semesterData) {
                // Calculate semester GPA
                $semesterData['gpa'] = $semesterData['total_credit_hours'] > 0
                    ? round($semesterData['total_credit_points'] / $semesterData['total_credit_hours'], 2)
                    : 0;
                
                // Sort results by course code
                usort($semesterData['results'], function($a, $b) {
                    return strcmp($a->course->code, $b->course->code);
                });
            }
            
            // Sort semesters by semester_number
            uasort($yearData['semesters'], function($a, $b) {
                return $a['semester']->semester_number - $b['semester']->semester_number;
            });

            // Calculate year GPA
            $yearGPAs[$yearId] = $yearData['year_credit_hours'] > 0
                ? round($yearData['year_credit_points'] / $yearData['year_credit_hours'], 2)
                : 0;

            // Calculate cumulative GPA up to this point
            $cumulativeGPAs[$yearId] = $runningCreditHours > 0
                ? round($runningCreditPoints / $runningCreditHours, 2)
                : 0;
        }

        // Sort academic years by start year
        ksort($groupedResults);

        return view('student.results', compact(
            'student',
            'groupedResults',
            'yearGPAs',
            'cumulativeGPAs'
        ));
    }
    
    /**
     * Show the student's transcript.
     */
    public function transcript()
    {
        $student = Auth::user()->student;
        
        // Get results
        $results = $student->results()
            ->with(['course', 'semester', 'academicYear'])
            ->orderBy('academic_year_id')
            ->orderBy('semester_id')
            ->get();
        
        // Group results by academic year and semester
        $groupedResults = [];
        foreach ($results as $result) {
            $academicYearId = $result->academic_year_id;
            $semesterId = $result->semester_id;
            
            if (!isset($groupedResults[$academicYearId])) {
                $groupedResults[$academicYearId] = [
                    'academic_year' => $result->academicYear,
                    'semesters' => []
                ];
            }
            
            if (!isset($groupedResults[$academicYearId]['semesters'][$semesterId])) {
                $groupedResults[$academicYearId]['semesters'][$semesterId] = [
                    'semester' => $result->semester,
                    'results' => [],
                    'gpa' => 0
                ];
            }
            
            $groupedResults[$academicYearId]['semesters'][$semesterId]['results'][] = $result;
        }
        
        // Calculate GPA for each semester
        foreach ($groupedResults as &$academicYear) {
            foreach ($academicYear['semesters'] as $semesterId => &$semesterData) {
                $semesterData['gpa'] = $student->calculateGPA($semesterId);
            }
        }
        
        $cgpa = $student->calculateCGPA();
        $classification = $student->getClassification();
        
        // Get institution settings from DB
        $institutionSettings = \DB::table('settings')->where('category', 'institution')->get();
        
        // Convert settings collection to associative array for easier access in the view
        $settings = [];
        foreach ($institutionSettings as $setting) {
            $settings[$setting->key] = $setting->value;
        }
        
        return view('student.transcript', compact('student', 'groupedResults', 'cgpa', 'classification', 'settings'));
    }

    /**
     * Show the profile edit form.
     */
    public function editProfile()
    {
        $user = Auth::user();
        $student = $user->student;
        return view('student.student-profile-edit', compact('user', 'student'));
    }

    /**
     * Update the student's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $student = $user->student;

        // If only updating profile photo
        if ($request->hasFile('profile_photo')) {
            $request->validate([
                'profile_photo' => ['required', 'image', 'max:2048'],
            ]);

            // Delete old photo if it exists
            if ($student->profile_photo) {
                Storage::disk('public')->delete($student->profile_photo);
            }

            // Store new photo
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $student->update(['profile_photo' => $path]);

            return redirect()->route('student.profile.edit')
                ->with('success', 'Profile photo updated successfully.');
        }

        // If updating other profile information
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('students')->ignore($student->id)],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'hometown' => ['nullable', 'string', 'max:255'],
            'gps_address' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],
            'emergency_contact_relationship' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
        ]);

        $student->update([
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'hometown' => $request->hometown,
            'gps_address' => $request->gps_address,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'emergency_contact_relationship' => $request->emergency_contact_relationship,
        ]);

        return redirect()->route('student.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Remove the student's profile photo.
     */
    public function removeProfilePhoto()
    {
        $user = Auth::user();
        $student = $user->student;

        if ($student->profile_photo) {
            Storage::disk('public')->delete($student->profile_photo);
            $student->update(['profile_photo' => null]);
        }

        return redirect()->route('student.profile.edit')
            ->with('success', 'Profile photo removed successfully.');
    }

    /**
     * Update the student's password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        
        // Different validation rules for first login vs regular password change
        if ($user->first_login) {
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
        } else {
            $request->validate([
                'current_password' => ['required', 'string'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            // Verify current password for non-first-login changes
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'The current password is incorrect.']);
            }
        }

        // Update password and first_login flag
        $user->password = Hash::make($request->password);
        $user->first_login = false;
        $user->save();

        // Redirect to intended URL or dashboard
        if ($request->session()->has('url.intended')) {
            $redirect = redirect()->intended(route('student.dashboard'));
        } else {
            $redirect = redirect()->route('student.dashboard');
        }

        return $redirect->with('success', 'Password updated successfully.');
    }
}
