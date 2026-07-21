<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\Student;
use Illuminate\Http\Request;

/**
 * Read-only student directory for roles that need to look up a student's contact/personal
 * details (e.g. Accountant) without seeing academic data - no grades, GPA, or results
 * anywhere in this controller, unlike StudentController.
 */
class StudentDirectoryController extends Controller
{
    /**
     * Display a listing of students with contact/personal details only.
     */
    public function index(Request $request)
    {
        $query = Student::with(['programme', 'classGroup']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('index_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('programme_id')) {
            $query->where('programme_id', $request->programme_id);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('hall')) {
            $query->where('hall', $request->hall);
        }

        $perPage = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [20, 50, 100, 200, 500], true)) {
            $perPage = 50;
        }

        $students = $query->orderBy('full_name')->paginate($perPage)->withQueryString();

        $programmes = Programme::orderBy('name')->get();
        $levels = Student::whereNotNull('level')->distinct()->orderBy('level')->pluck('level');
        $halls = Student::whereNotNull('hall')->where('hall', '!=', '')->distinct()->orderBy('hall')->pluck('hall');

        return view('student-directory.index', compact('students', 'programmes', 'levels', 'halls'));
    }

    /**
     * Show a student's contact/personal details only - deliberately no results/GPA relation
     * loaded or displayed here.
     */
    public function show(Student $student)
    {
        $student->load(['programme', 'classGroup']);

        return view('student-directory.show', compact('student'));
    }
}
