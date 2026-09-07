<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\Student;
use App\Models\BiometricRegistration;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BiometricRegistrationController extends Controller
{
    /**
     * Display a listing of students and their biometric verification status for a selected semester.
     */
    public function index(Request $request)
    {
        $semester = $request->filled('semester_id')
            ? Semester::find($request->semester_id)
            : Semester::where('is_current', true)->first();

        $semesters = Semester::with('academicYear')->orderByDesc('id')->get();
        $programmes = Programme::orderBy('name')->get();

        $query = Student::with('programme');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('full_name', 'like', "%{$searchTerm}%")
                    ->orWhere('index_number', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('programme_id')) {
            $query->where('programme_id', $request->programme_id);
        }

        // Narrow to continuing students (Level 100-400) who actually registered for the selected
        // semester's academic year - they're the ones expected to check in, so this matches what
        // the dashboard's verified / unverified counts are measured against. Graduates are left
        // out even though they still carry registration rows from their final year.
        if ($request->boolean('registered') && $semester) {
            $query->where('status', 'active')
                ->whereIn('level', [100, 200, 300, 400])
                ->whereIn('id', Registration::where('academic_year_id', $semester->academic_year_id)
                    ->distinct()
                    ->pluck('student_id'));
        }

        if ($request->filled('status') && $semester) {
            $verifiedIds = $semester->biometricRegistrations()->pluck('student_id');
            if ($request->status === 'verified') {
                $query->whereIn('id', $verifiedIds);
            } elseif ($request->status === 'not_verified') {
                $query->whereNotIn('id', $verifiedIds);
            }
        }

        $perPage = (int) $request->input('per_page', 20);
        $students = $query->orderBy('full_name')->paginate($perPage)->withQueryString();

        $registrations = $semester
            ? $semester->biometricRegistrations()->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id')
            : collect();

        return view('biometric.registrations.index', compact('students', 'semester', 'semesters', 'programmes', 'registrations'));
    }

    /**
     * Show one student's biometric verification history across semesters.
     */
    public function show(Student $student)
    {
        $registrations = $student->biometricRegistrations()
            ->with(['semester.academicYear', 'verifiedBy'])
            ->orderByDesc('verified_at')
            ->get();

        $semesters = Semester::with('academicYear')->orderByDesc('id')->get();

        return view('biometric.registrations.show', compact('student', 'registrations', 'semesters'));
    }

    /**
     * Manually mark a student as biometrically verified for a semester.
     */
    public function store(Request $request, Student $student)
    {
        $validator = Validator::make($request->all(), [
            'semester_id' => 'required|exists:semesters,id',
            'notes' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $registration = BiometricRegistration::updateOrCreate(
            [
                'student_id' => $student->id,
                'semester_id' => $request->semester_id,
            ],
            [
                'verified_at' => now(),
                'source' => 'manual',
                'verified_by' => Auth::id(),
                'notes' => $request->notes,
            ]
        );

        ActivityLogger::log(
            'biometric-manual-verify',
            "Manually verified biometric registration for {$student->full_name} ({$student->index_number})"
        );

        return redirect()->route('biometric-verifications.show', $student)
            ->with('success', 'Student marked as biometrically verified.');
    }

    /**
     * Mark several selected students as biometrically verified for a semester in one action.
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'semester_id' => 'required|exists:semesters,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('biometric-verifications.index', $request->query())
                ->with('error', 'Select a semester and at least one student to verify.');
        }

        $count = 0;

        foreach ($request->student_ids as $studentId) {
            BiometricRegistration::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'semester_id' => $request->semester_id,
                ],
                [
                    'verified_at' => now(),
                    'source' => 'manual',
                    'verified_by' => Auth::id(),
                    'notes' => 'Bulk verified',
                ]
            );
            $count++;
        }

        ActivityLogger::log(
            'biometric-bulk-verify',
            "Bulk-verified biometric registration for {$count} student(s) for semester #{$request->semester_id}"
        );

        return redirect()->route('biometric-verifications.index', $request->query())
            ->with('success', "Verified {$count} student(s) successfully.");
    }

    /**
     * Remove a mistaken verification entry.
     */
    public function destroy(BiometricRegistration $registration)
    {
        $student = $registration->student;
        $registration->delete();

        ActivityLogger::log(
            'biometric-verify-removed',
            "Removed biometric verification for {$student?->full_name} ({$student?->index_number})"
        );

        return redirect()->route('biometric-verifications.show', $student)
            ->with('success', 'Verification entry removed.');
    }
}
