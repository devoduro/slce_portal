<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Registration;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    /**
     * Show the student's registration status and currently registered courses.
     */
    public function index()
    {
        $student = Auth::user()->student;
        $currentSemester = Semester::with('academicYear')->where('is_current', true)->first();

        $registrations = collect();
        $meetsThreshold = false;
        $percentage = 0;
        $balance = 0;
        $feeStructure = null;
        $isBiometricVerified = false;

        if ($currentSemester) {
            $registrations = $student->registrations()
                ->with('course')
                ->where('semester_id', $currentSemester->id)
                ->where('status', 'registered')
                ->get();

            $academicYear = $currentSemester->academicYear;
            $feeStructure = $student->applicableFeeStructure($academicYear);
            $percentage = $student->paymentPercentage($academicYear);
            $balance = $student->feeBalance($academicYear);
            $meetsThreshold = $student->meetsRegistrationThreshold($currentSemester);
            $isBiometricVerified = $student->hasBiometricVerification($currentSemester);
        }

        return view('student.registration.index', compact(
            'student',
            'currentSemester',
            'registrations',
            'meetsThreshold',
            'percentage',
            'balance',
            'feeStructure',
            'isBiometricVerified'
        ));
    }

    /**
     * Show the course selection form for the current semester.
     */
    public function create()
    {
        $student = Auth::user()->student;
        $currentSemester = Semester::with('academicYear')->where('is_current', true)->first();

        if (!$this->canRegister($student, $currentSemester)) {
            return redirect()->route('student.registration.index')
                ->with('error', 'Course registration is not currently available to you.');
        }

        $availableCourses = $this->availableCourses($student, $currentSemester);

        $registeredCourseIds = $student->registrations()
            ->where('semester_id', $currentSemester->id)
            ->where('status', 'registered')
            ->pluck('course_id')
            ->toArray();

        return view('student.registration.create', compact('currentSemester', 'availableCourses', 'registeredCourseIds'));
    }

    /**
     * Register the selected courses for the current semester.
     */
    public function store(Request $request)
    {
        $student = Auth::user()->student;
        $currentSemester = Semester::with('academicYear')->where('is_current', true)->first();

        if (!$this->canRegister($student, $currentSemester)) {
            return redirect()->route('student.registration.index')
                ->with('error', 'Course registration is not currently available to you.');
        }

        $request->validate([
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'integer',
        ]);

        $allowedCourseIds = $this->availableCourses($student, $currentSemester)->pluck('id')->toArray();
        $selectedCourseIds = array_intersect($request->course_ids, $allowedCourseIds);

        if (empty($selectedCourseIds)) {
            return redirect()->route('student.registration.create')
                ->with('error', 'None of the selected courses are available for registration.');
        }

        foreach ($selectedCourseIds as $courseId) {
            Registration::firstOrCreate([
                'student_id' => $student->id,
                'course_id' => $courseId,
                'semester_id' => $currentSemester->id,
                'academic_year_id' => $currentSemester->academic_year_id,
            ], [
                'status' => 'registered',
            ]);
        }

        return redirect()->route('student.registration.index')
            ->with('success', 'Courses registered successfully.');
    }

    /**
     * Drop a registered course while registration is still open.
     */
    public function destroy(Registration $registration)
    {
        $student = Auth::user()->student;

        if ($registration->student_id !== $student->id) {
            abort(403);
        }

        $semester = $registration->semester;

        if (!$semester || !$semester->registration_open) {
            return redirect()->route('student.registration.index')
                ->with('error', 'Registration is closed for this semester, so courses cannot be dropped.');
        }

        $registration->delete();

        return redirect()->route('student.registration.index')
            ->with('success', 'Course dropped successfully.');
    }

    /**
     * Determine whether the student is currently allowed to register courses.
     */
    protected function canRegister($student, ?Semester $semester): bool
    {
        if (!$student || !$semester || !$semester->registration_open) {
            return false;
        }

        return $student->hasBiometricVerification($semester) && $student->meetsRegistrationThreshold($semester);
    }

    /**
     * Get the courses available to a student for a given semester (their programme's courses in that semester).
     */
    protected function availableCourses($student, ?Semester $semester)
    {
        if (!$semester) {
            return collect();
        }

        return Course::where('semester_id', $semester->id)
            ->whereHas('programmes', function ($query) use ($student) {
                $query->where('programme_id', $student->programme_id);
            })
            ->orderBy('code')
            ->get();
    }
}
