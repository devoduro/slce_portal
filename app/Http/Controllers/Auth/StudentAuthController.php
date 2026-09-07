<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TimetableController;
use App\Models\CaScoreSetting;
use App\Models\ContinuousAssessment;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StsPlacement;
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

        // Today's classes for the dashboard timetable widget, based on the student's class group
        $todayEntries = collect();
        if ($currentSemester && $student->class_group_id) {
            $todayEntries = TimetableController::fetchEntries([
                'class_group_id' => $student->class_group_id,
                'semester_id' => $currentSemester->id,
            ])->where('day_of_week', now()->dayOfWeek)->sortBy('start_time')->values();
        }

        return view('student.dashboard', compact(
            'student',
            'results',
            'cgpa',
            'cgpaChange',
            'classification',
            'totalCredits',
            'remainingCredits',
            'currentCourses',
            'currentSemester',
            'todayEntries'
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

            // Add result to appropriate semester (always shown, even if it doesn't count toward GPA)
            if (isset($groupedResults[$academicYearId]['semesters'][$semesterId])) {
                $groupedResults[$academicYearId]['semesters'][$semesterId]['results'][] = $result;

                // Calculate credit points and hours
                $creditHours = $result->course->credit_hours ?? 0;
                if ($creditHours <= 0) continue; // Skip if invalid credit hours

                $creditPoints = $creditHours * ($result->grade_point ?? 0);

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

        // Convert to array and ensure it's sorted chronologically, oldest first
        $groupedResults = array_values(array_filter(collect($groupedResults)->sortBy(function($group) {
            return $group['academic_year']->start_date;
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
     * Show the student's Continuous Assessment breakdown for their registered courses.
     */
    public function continuousAssessment()
    {
        $student = Auth::user()->student;

        // Scoped to the semester the student is actually in right now. Without this, every
        // registration the student has ever made comes back, so anyone who has been promoted
        // sees last year's courses (taken at their previous level) instead of their current
        // ones - and the CaScoreSetting lookup below, which keys off the student's *current*
        // level, would be showing the wrong maximum scores against those older rows.
        $currentSemester = Semester::where('is_current', true)->first();
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();

        $registrations = Registration::where('student_id', $student->id)
            ->where('status', 'registered')
            ->when($currentAcademicYear, fn ($query) => $query->where('academic_year_id', $currentAcademicYear->id))
            ->when($currentSemester, fn ($query) => $query->where('semester_id', $currentSemester->id))
            ->with(['course', 'semester'])
            ->get()
            ->filter(fn ($registration) => $registration->course !== null);

        $rows = $registrations->map(function ($registration) use ($student) {
            $course = $registration->course;
            // The registration's own semester is what the CA record is keyed by - the course's
            // semester can differ (a course row belongs to the semester it was defined for).
            $semester = $registration->semester ?? $course->semester;

            $ca = ContinuousAssessment::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->where('semester_id', $semester?->id)
                ->where('academic_year_id', $registration->academic_year_id)
                ->first();

            $setting = CaScoreSetting::where('level', $student->level)->first();
            $attendanceScore = (float) ($ca->attendance_score ?? 0);

            $total = $attendanceScore
                + (float) ($ca->project_score ?? 0)
                + (float) ($ca->assignment_score ?? 0)
                + (float) ($ca->mid_semester_score ?? 0);

            // STS and Internship aren't marked on the four standard CA components - they are
            // marked on whatever criteria the STS Unit defined for the student's level, which
            // vary in number and name. Their marks live against the placement, so the row shows
            // that total and points at the STS page for the criterion-by-criterion breakdown.
            $stsSummary = null;

            if ($course->is_sts_course) {
                $placement = StsPlacement::with('scores')
                    ->where('student_id', $student->id)
                    ->whereHas('stsTerm', fn ($query) => $query->where('semester_id', $semester?->id))
                    ->first();

                $stsSummary = $placement?->scoreSummary();
                $total = (float) ($stsSummary['awarded'] ?? 0);
            }

            return [
                'course' => $course,
                'semester' => $semester,
                'ca' => $ca,
                'setting' => $setting,
                'attendance_score' => $attendanceScore,
                'total' => $total,
                'sts_summary' => $stsSummary,
            ];
        });

        return view('student.continuous-assessment', compact('student', 'rows'));
    }

    /**
     * Show the student's own weekly timetable for their class group.
     */
    public function timetable(Request $request)
    {
        $student = Auth::user()->student;

        $semesters = Semester::orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();
        $semester = $request->filled('semester_id')
            ? $semesters->firstWhere('id', (int) $request->semester_id)
            : $semesters->firstWhere('is_current', true);

        $entries = collect();
        $slotLabels = [];
        $classSummary = null;

        if ($semester && $student->class_group_id) {
            $entries = TimetableController::fetchEntries(['class_group_id' => $student->class_group_id, 'semester_id' => $semester->id]);
            TimetableController::applyGridPositions($entries);
            $slotLabels = TimetableController::gridSlotLabels();
            $classSummary = TimetableController::classSummary($student->class_group_id, $semester->id);
        }

        return view('student.timetable', compact('student', 'semesters', 'semester', 'entries', 'slotLabels', 'classSummary'));
    }

    /**
     * Print the student's own timetable for their class group.
     */
    public function printTimetable(Request $request)
    {
        $student = Auth::user()->student;

        abort_unless($student->class_group_id, 404, 'You have not been assigned to a class yet.');

        $semesterId = $request->filled('semester_id')
            ? (int) $request->semester_id
            : optional(Semester::where('is_current', true)->first())->id;

        abort_unless($semesterId, 404, 'No semester is available to print.');

        return TimetableController::buildPrintView(['class_group_id' => $student->class_group_id, 'semester_id' => $semesterId], $student);
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
