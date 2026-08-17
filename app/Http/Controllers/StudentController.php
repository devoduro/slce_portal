<?php

namespace App\Http\Controllers;

use App\Models\ClassGroup;
use App\Models\Programme;
use App\Models\Student;
use App\Models\User;
use App\Imports\StudentsImport;
use App\Exports\StudentsExport;
use App\Services\StudentPdfExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    /**
     * Export students list to Excel
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(new StudentsExport($request), 'students.xlsx');
    }

    /**
     * Export students list to PDF
     */
    public function exportPdf(Request $request)
    {
        $exporter = new StudentPdfExport($request);
        return $exporter->export();
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Student::with('programme');
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('full_name', 'like', "%{$searchTerm}%")
                  ->orWhere('index_number', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }
        
        // Filter by programme
        if ($request->has('programme_id') && $request->programme_id) {
            $query->where('programme_id', $request->programme_id);
        }
        
        // Filter by gender
        if ($request->has('gender') && $request->gender) {
            $query->where('gender', $request->gender);
        }

        // Filter by level
        if ($request->has('level') && $request->level) {
            $query->where('level', $request->level);
        }

        $students = $query->paginate(10)->withQueryString();
        $programmes = Programme::all();
        
        return view('students.index', compact('students', 'programmes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $programmes = Programme::all();
        $classGroups = $this->classGroupOptions();
        return view('students.create', compact('programmes', 'classGroups'));
    }

    /**
     * Class groups labelled with programme + level for the manual assignment dropdown.
     */
    protected function classGroupOptions()
    {
        return ClassGroup::with('programme')
            ->orderBy('programme_id')
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'index_number' => 'required|string|unique:students,index_number',
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'nullable|integer|min:100|max:800',
            'class_group_id' => 'nullable|exists:class_groups,id',
            'profile_photo' => 'nullable|image|max:2048',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('students.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();

        if (($data['level'] ?? '') === '') {
            $data['level'] = null;
        }

        if (($data['class_group_id'] ?? '') === '') {
            $data['class_group_id'] = null;
        }

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $data['profile_photo'] = $path;
        }

        Student::create($data);

        return redirect()->route('students.index')
            ->with('success', 'Student created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $student = Student::with(['programme', 'classGroup', 'results' => function($query) {
            $query->with(['course', 'semester', 'academicYear'])
                  ->orderBy('academic_year_id')
                  ->orderBy('semester_id');
        }])->findOrFail($id);
        
        // Group results by academic year and semester
        $groupedResults = [];
        foreach ($student->results as $result) {
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
        
        return view('students.show', compact('student', 'groupedResults', 'cgpa', 'classification'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $student = Student::with('programme')->findOrFail($id);
        $programmes = Programme::all();
        $classGroups = $this->classGroupOptions();
        return view('students.edit', compact('student', 'programmes', 'classGroups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $student = Student::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'index_number' => 'required|string|unique:students,index_number,' . $id,
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'nullable|integer|min:100|max:800',
            'class_group_id' => 'nullable|exists:class_groups,id',
            'profile_photo' => 'nullable|image|max:2048',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('students.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except(['profile_photo']);

        if (($data['level'] ?? '') === '') {
            $data['level'] = null;
        }

        if (($data['class_group_id'] ?? '') === '') {
            $data['class_group_id'] = null;
        }

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($student->profile_photo) {
                Storage::disk('public')->delete($student->profile_photo);
            }

            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $data['profile_photo'] = $path;
        }

        $student->update($data);

        return redirect()->route('students.index')
            ->with('success', 'Student updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $student = Student::findOrFail($id);
        
        // Delete profile photo if exists
        if ($student->profile_photo) {
            Storage::disk('public')->delete($student->profile_photo);
        }
        
        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Student deleted successfully.');
    }
    
    /**
     * Show the form for importing students from Excel.
     */
    public function importForm()
    {
        return view('students.import');
    }
    
    /**
     * Import students from Excel file.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('students.import.form')
                ->withErrors($validator)
                ->withInput();
        }
        
        if (!class_exists('ZipArchive')) {
            return redirect()->route('students.import.form')
                ->with('error', 'The PHP zip extension is not loaded. Please restart the PHP server and try again. If the problem persists, enable extension=zip in C:\xampp\php\php.ini and restart.');
        }

        try {
            Excel::import(new StudentsImport, $request->file('excel_file'));

            return redirect()->route('students.index')
                ->with('success', 'Students imported successfully.');
        } catch (\Exception $e) {
            return redirect()->route('students.import.form')
                ->with('error', 'Error importing students: ' . $e->getMessage());
        }
    }
    
    /**
     * Download sample Excel template for student import.
     */
    public function downloadTemplate()
    {
        $filePath = storage_path('app/templates/students_import_template.xlsx');
        
        if (!file_exists($filePath)) {
            return redirect()->route('students.import.form')
                ->with('error', 'Template file not found.');
        }
        
        return response()->download($filePath, 'students_import_template.xlsx');
    }
    
    /**
     * Create a user account for a student.
     */
    public function createUserAccount(string $id)
    {
        $student = Student::findOrFail($id);
        
        // Check if student already has a user account
        if ($student->user) {
            return redirect()->route('students.show', $id)
                ->with('warning', 'Student already has a user account.');
        }
        
        // Format date of birth as YYYYMMDD for default password
        $defaultPassword = date('Ymd', strtotime($student->date_of_birth));
        
        try {
            // Create user account
            $user = User::create([
                'name' => $student->full_name,
                'email' => $student->email ?? $student->index_number . '@st.uew.edu.gh',
                'password' => Hash::make($defaultPassword),
                'role' => 'student',
                'index_number' => $student->index_number,
                'student_id' => $student->id,
                'first_login' => true
            ]);
            
            return redirect()->route('students.show', $id)
                ->with('success', 'User account created successfully. Default password is the student\'s is password.');
        } catch (\Exception $e) {
            return redirect()->route('students.show', $id)
                ->with('error', 'Failed to create user account. Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Create user accounts for all students without user accounts.
     */
    public function bulkCreateUserAccounts(Request $request)
    {
        $created = 0;
        $errors = [];
        
        $students = Student::whereDoesntHave('user')->get();
        
        foreach ($students as $student) {
            try {
                // Format date of birth as YYYYMMDD for default password
                $defaultPassword = "password";
                
                // Create user account
                User::create([
                    'name' => $student->full_name,
                    'email' => $student->email ?? $student->index_number . '@st.uew.edu.gh',
                    'password' => Hash::make($defaultPassword),
                    'role' => 'student',
                    'index_number' => $student->index_number,
                    'student_id' => $student->id,
                    'first_login' => true
                ]);
                
                $created++;
            } catch (\Exception $e) {
                $errors[] = "Failed to create account for {$student->index_number}: {$e->getMessage()}";
            }
        }
        
        if ($created > 0) {
            $message = "Created {$created} user accounts. Default password is each student's date of birth (YYYYMMDD).";
            if (!empty($errors)) {
                $message .= "\n\nErrors:\n" . implode("\n", $errors);
            }
            return redirect()->route('students.index')->with('success', $message);
        } else {
            return redirect()->route('students.index')
                ->with('error', "No user accounts were created.\n\nErrors:\n" . implode("\n", $errors));
        }
    }

    /**
     * Reset all student passwords to a default password.
     */
    public function bulkResetPasswords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'default_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return redirect()->route('students.index')
                ->withErrors($validator)
                ->withInput();
        }

        // Increase execution time and memory for bulk operation
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M');

        $defaultPassword = $request->input('default_password');
        $updated = 0;
        $errors = [];
        
        // Hash the password once instead of for each user
        $hashedPassword = Hash::make($defaultPassword);
        
        // Get all users with student role
        $users = User::where('role', 'student')->get();
        
        foreach ($users as $user) {
            try {
                $user->password = $hashedPassword;
                $user->first_login = true;
                $user->save();
                $updated++;
            } catch (\Exception $e) {
                $errors[] = "Failed to reset password for user ID {$user->id}: {$e->getMessage()}";
            }
        }
        
        if ($updated > 0) {
            $message = "Successfully reset passwords for {$updated} student(s). Default password: {$defaultPassword}";
            if (!empty($errors)) {
                $message .= " | Errors: " . count($errors) . " failed.";
            }
            return redirect()->route('students.index')->with('success', $message);
        } else {
            return redirect()->route('students.index')
                ->with('error', "No passwords were reset. " . implode(", ", $errors));
        }
    }

    /**
     * Display the student's results.
     */
    public function results(string $id)
    {
        // Find the student with necessary relationships
        $student = Student::with([
            'programme',
            'results' => function($query) {
                $query->select('results.*')
                    ->join('academic_years', 'results.academic_year_id', '=', 'academic_years.id')
                    ->join('semesters', 'results.semester_id', '=', 'semesters.id')
                    ->orderByRaw('YEAR(academic_years.start_date) ASC')
                    ->orderBy('semesters.semester_number', 'asc');
            },
            'results.course' => function($query) {
                $query->select('id', 'code', 'title', 'credit_hours', 'semester_id');
            },
            'results.semester' => function($query) {
                $query->select('id', 'name', 'semester_number');
            },
            'results.academicYear' => function($query) {
                $query->select('id', 'name', 'start_date', 'end_date');
            }
        ])->findOrFail($id);

        // Initialize arrays for calculations
        $groupedResults = [];
        $yearGPAs = [];
        $cumulativeGPAs = [];
        $runningCreditPoints = 0;
        $runningCreditHours = 0;

        // Get all academic years with results and ensure we have the full model
        $results = $student->results()->with(['academicYear', 'semester', 'course'])->get();
        
        // Filter and get unique academic years
        $academicYears = $results->filter(function($result) {
            return $result->academicYear && $result->semester && $result->course;
        })->pluck('academicYear')->unique();
        
        // Initialize the complete structure
        if ($academicYears->isNotEmpty()) {
            foreach ($academicYears as $academicYear) {
                if (!$academicYear) continue; // Skip if academic year is null
                
                $groupedResults[$academicYear->id] = [
                    'academic_year' => $academicYear,
                    'semesters' => [],
                    'year_credit_points' => 0,
                    'year_credit_hours' => 0
                ];
                
                // Initialize semesters that have results for this academic year
                $yearResults = $results->where('academic_year_id', $academicYear->id);
                $yearSemesters = $yearResults->pluck('semester')->unique();
                
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
        
        // Now process all results
        foreach ($student->results as $result) {
            $academicYearId = $result->academic_year_id;
            $semesterId = $result->semester_id;

            // Add result to appropriate semester (always shown, even if it doesn't count toward GPA)
            $groupedResults[$academicYearId]['semesters'][$semesterId]['results'][] = $result;

            // A superseded original (one with a resit on record) is shown above but excluded from the totals
            if (!$result->counts_for_gpa) {
                continue;
            }

            // Calculate credit points and hours
            $creditHours = $result->course->credit_hours;
            $creditPoints = $creditHours * $result->grade_point;

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
}
