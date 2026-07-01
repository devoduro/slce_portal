<?php

namespace App\Http\Controllers;

use App\Models\Programme;
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

        if ($request->filled('status') && $semester) {
            $verifiedIds = $semester->biometricRegistrations()->pluck('student_id');
            if ($request->status === 'verified') {
                $query->whereIn('id', $verifiedIds);
            } elseif ($request->status === 'not_verified') {
                $query->whereNotIn('id', $verifiedIds);
            }
        }

        $students = $query->orderBy('full_name')->paginate(20)->withQueryString();

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
