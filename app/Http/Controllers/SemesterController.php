<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Result;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SemesterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $semesters = Semester::with('academicYear')
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('semester_number', 'asc')
            ->paginate(20);
            
        return view('semesters.index', compact('semesters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        return view('semesters.create', compact('academicYears'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_number' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return redirect()->route('semesters.create')
                ->withErrors($validator)
                ->withInput();
        }
        
        // Check if semester with same semester_number already exists for this academic year
        $existingSemester = Semester::where('academic_year_id', $request->input('academic_year_id'))
            ->where('semester_number', $request->input('semester_number'))
            ->first();
            
        if ($existingSemester) {
            return redirect()->route('semesters.create')
                ->withErrors(['semester_number' => 'A semester with this number already exists for the selected academic year.'])
                ->withInput();
        }
        
        Semester::create($request->all());
        
        return redirect()->route('semesters.index')
            ->with('success', 'Semester created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $semester = Semester::with('academicYear')->findOrFail($id);
        
        // Get courses for this semester
        $courses = Course::where('semester_id', $id)
            ->with('programmes')
            ->orderBy('code')
            ->get();
            
        // Get result statistics for this semester
        $resultStats = Result::where('semester_id', $id)
            ->select(
                DB::raw('COUNT(*) as total_results'),
                DB::raw('AVG(score) as average_score'),
                DB::raw('COUNT(CASE WHEN grade_point >= 1.0 THEN 1 END) as pass_count'),
                DB::raw('COUNT(CASE WHEN grade_point < 1.0 THEN 1 END) as fail_count')
            )
            ->first();
            
        // Get GPA distribution for this semester
        $gpaDistribution = DB::table('results')
            ->select(
                'student_id',
                DB::raw('AVG(grade_point) as gpa')
            )
            ->where('semester_id', $id)
            ->groupBy('student_id')
            ->get()
            ->groupBy(function($item) {
                if ($item->gpa >= 3.5) return '3.5-4.0';
                else if ($item->gpa >= 3.0) return '3.0-3.49';
                else if ($item->gpa >= 2.5) return '2.5-2.99';
                else if ($item->gpa >= 2.0) return '2.0-2.49';
                else if ($item->gpa >= 1.0) return '1.0-1.99';
                else return '0.0-0.99';
            })
            ->map(function($group) {
                return count($group);
            });
            
        return view('semesters.show', compact(
            'semester', 
            'courses', 
            'resultStats', 
            'gpaDistribution'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $semester = Semester::findOrFail($id);
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        
        return view('semesters.edit', compact('semester', 'academicYears'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $semester = Semester::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_number' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return redirect()->route('semesters.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }
        
        // Check if semester with same semester_number already exists for this academic year (excluding this one)
        $existingSemester = Semester::where('academic_year_id', $request->input('academic_year_id'))
            ->where('semester_number', $request->input('semester_number'))
            ->where('id', '!=', $id)
            ->first();
            
        if ($existingSemester) {
            return redirect()->route('semesters.edit', $id)
                ->withErrors(['semester_number' => 'A semester with this number already exists for the selected academic year.'])
                ->withInput();
        }
        
        $semester->update($request->all());
        
        return redirect()->route('semesters.index')
            ->with('success', 'Semester updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $semester = Semester::findOrFail($id);
        
        // Check if there are any courses associated with this semester
        if ($semester->courses()->count() > 0) {
            return redirect()->route('semesters.index')
                ->with('error', 'Cannot delete semester with associated courses.');
        }
        
        // Check if there are any results associated with this semester
        if ($semester->results()->count() > 0) {
            return redirect()->route('semesters.index')
                ->with('error', 'Cannot delete semester with associated results.');
        }
        
        $semester->delete();
        
        return redirect()->route('semesters.index')
            ->with('success', 'Semester deleted successfully.');
    }
    
    /**
     * Filter semesters by academic year.
     */
    public function filterByAcademicYear(Request $request)
    {
        $academicYearId = $request->input('academic_year_id');
        
        $semesters = Semester::with('academicYear')
            ->where('academic_year_id', $academicYearId)
            ->orderBy('semester_number', 'asc')
            ->paginate(20);
            
        $academicYear = AcademicYear::findOrFail($academicYearId);
            
        return view('semesters.index', compact('semesters', 'academicYear'));
    }
    
    /**
     * Set current semester.
     */
    public function setCurrent(Semester $semester)
    {
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Unset current semester flag for all semesters
            DB::statement('UPDATE semesters SET is_current = 0');
            
            // Set current semester
            DB::statement('UPDATE semesters SET is_current = 1 WHERE id = ?', [$semester->id]);
            
            // Set current academic year
            $academicYear = AcademicYear::findOrFail($semester->academic_year_id);
            DB::statement('UPDATE academic_years SET is_current = 0');
            DB::statement('UPDATE academic_years SET is_current = 1 WHERE id = ?', [$academicYear->id]);
            
            DB::commit();
            
            return redirect()->route('semesters.index')
                ->with('success', 'Current semester set successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('semesters.index')
                ->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * List courses for a semester.
     */
    public function courses(string $id)
    {
        $semester = Semester::with('academicYear')->findOrFail($id);
        
        $courses = Course::where('semester_id', $id)
            ->with('programmes')
            ->orderBy('code')
            ->paginate(20);
            
        return view('semesters.courses', compact('semester', 'courses'));
    }
    
    /**
     * List results for a semester.
     */
    public function results(string $id)
    {
        $semester = Semester::with('academicYear')->findOrFail($id);
        
        $results = Result::where('semester_id', $id)
            ->with(['student', 'course'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('semesters.results', compact('semester', 'results'));
    }
}
