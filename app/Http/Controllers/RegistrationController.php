<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Registration;
use App\Models\Semester;
use App\Services\FeeLedgerService;
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
        $totalArrears = $student->totalArrears();
        $ledger = FeeLedgerService::ledgerFor($student);

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
            'isBiometricVerified',
            'totalArrears',
            'ledger'
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
     * Sync the student's registered courses for the current semester to match the submitted
     * selection - registers newly-checked courses and drops previously-registered courses that
     * were unchecked, so the same form can be used to both register and edit a registration.
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

        $currentRegistrations = $student->registrations()
            ->where('semester_id', $currentSemester->id)
            ->where('status', 'registered')
            ->get();
        $currentCourseIds = $currentRegistrations->pluck('course_id')->toArray();

        $toAdd = array_diff($selectedCourseIds, $currentCourseIds);
        $toRemove = array_diff($currentCourseIds, $selectedCourseIds);

        foreach ($toAdd as $courseId) {
            Registration::firstOrCreate([
                'student_id' => $student->id,
                'course_id' => $courseId,
                'semester_id' => $currentSemester->id,
                'academic_year_id' => $currentSemester->academic_year_id,
            ], [
                'status' => 'registered',
            ]);
        }

        if (!empty($toRemove)) {
            $currentRegistrations->whereIn('course_id', $toRemove)->each->delete();
        }

        return redirect()->route('student.registration.index')
            ->with('success', 'Course registration updated successfully.');
    }

    /**
     * Print the student's already-submitted registration slip for the current semester.
     * Only allowed once courses have actually been registered - there is nothing to print
     * before submission.
     */
    public function print()
    {
        $student = Auth::user()->student;
        $currentSemester = Semester::with('academicYear')->where('is_current', true)->first();

        $courses = collect();
        if ($currentSemester) {
            $courses = Course::whereIn('id', $student->registrations()
                ->where('semester_id', $currentSemester->id)
                ->where('status', 'registered')
                ->pluck('course_id'))
                ->orderBy('code')
                ->get();
        }

        if ($courses->isEmpty()) {
            return redirect()->route('student.registration.index')
                ->with('error', 'You have not registered any courses yet, so there is nothing to print.');
        }

        $settings = \Illuminate\Support\Facades\DB::table('settings')->where('category', 'institution')->pluck('value', 'key')->toArray();

        return view('student.registration.print', [
            'student' => $student,
            'semester' => $currentSemester,
            'courses' => $courses,
            'settings' => $settings,
        ]);
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

        if (!$semester || !$semester->isRegistrationOpen()) {
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
        if (!$student || !$semester || !$semester->isRegistrationOpen()) {
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
            ->where('level', $student->level)
            ->whereHas('programmes', function ($query) use ($student) {
                $query->where('programme_id', $student->programme_id);
            })
            ->orderBy('code')
            ->get();
    }
}
