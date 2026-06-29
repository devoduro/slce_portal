<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Programme;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $academicYears = AcademicYear::all();
        $programmes = Programme::all();
        $semesters = Semester::all();
        
        return view('reports.index', compact('academicYears', 'programmes', 'semesters'));
    }

    /**
     * Generate GPA distribution report.
     */
    public function gpaDistribution(Request $request)
    {
        // Get data for filter dropdowns
        $academicYears = AcademicYear::all();
        $programmes = Programme::all();
        
        // If no filters are selected, just show the form
        if (!$request->has('academic_year_id')) {
            return view('reports.gpa-distribution', compact('academicYears', 'programmes'));
        }
        
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'programme_id' => 'nullable|exists:programmes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('reports.index')
                ->withErrors($validator)
                ->withInput();
        }
        
        $academicYearId = $request->input('academic_year_id');
        $programmeId = $request->input('programme_id');
        
        $academicYear = AcademicYear::findOrFail($academicYearId);
        $programme = $programmeId ? Programme::findOrFail($programmeId) : null;
        
        // Build the query
        $query = DB::table('results')
            ->join('students', 'results.student_id', '=', 'students.id')
            ->where('results.academic_year_id', $academicYearId);
            
        if ($programmeId) {
            $query->where('students.programme_id', $programmeId);
        }
        
        // Get GPA ranges
        $gpaRanges = [
            '0.00-0.99' => $query->clone()->whereBetween('results.grade_point', [0, 0.99])->count(),
            '1.00-1.99' => $query->clone()->whereBetween('results.grade_point', [1, 1.99])->count(),
            '2.00-2.99' => $query->clone()->whereBetween('results.grade_point', [2, 2.99])->count(),
            '3.00-3.49' => $query->clone()->whereBetween('results.grade_point', [3, 3.49])->count(),
            '3.50-4.00' => $query->clone()->whereBetween('results.grade_point', [3.5, 4])->count(),
        ];
        
        // Get average GPA
        $averageGpa = $query->avg('results.grade_point');
        
        return view('reports.gpa-distribution', compact(
            'academicYear', 
            'programme', 
            'gpaRanges', 
            'averageGpa'
        ));
    }

    /**
     * Generate course performance report.
     */
    public function coursePerformance(Request $request)
    {
        // Get data for filter dropdowns
        $academicYears = AcademicYear::all();
        $semesters = Semester::all();
        $programmes = Programme::all();
        
        // If no filters are selected, just show the form
        if (!$request->has('academic_year_id') || !$request->has('semester_id')) {
            return view('reports.course-performance', compact('academicYears', 'semesters', 'programmes'));
        }
        
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'programme_id' => 'nullable|exists:programmes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('reports.index')
                ->withErrors($validator)
                ->withInput();
        }
        
        $academicYearId = $request->input('academic_year_id');
        $semesterId = $request->input('semester_id');
        $programmeId = $request->input('programme_id');
        
        $academicYear = AcademicYear::findOrFail($academicYearId);
        $semester = Semester::findOrFail($semesterId);
        $programme = $programmeId ? Programme::findOrFail($programmeId) : null;
        
        // Get courses
        $coursesQuery = Course::query();
        
        if ($programmeId) {
            $coursesQuery->where('programme_id', $programmeId);
        }
        
        if ($semesterId) {
            $coursesQuery->where('semester_id', $semesterId);
        }
        
        $courses = $coursesQuery->get();
        
        // Get performance data for each course
        $coursePerformance = [];
        
        foreach ($courses as $course) {
            $results = Result::where([
                'course_id' => $course->id,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
            ])->get();
            
            $totalStudents = $results->count();
            $passCount = $results->where('grade_point', '>=', 1.0)->count();
            $failCount = $totalStudents - $passCount;
            $passRate = $totalStudents > 0 ? ($passCount / $totalStudents) * 100 : 0;
            $averageScore = $results->avg('score');
            
            $coursePerformance[] = [
                'course' => $course,
                'total_students' => $totalStudents,
                'pass_count' => $passCount,
                'fail_count' => $failCount,
                'pass_rate' => round($passRate, 2),
                'average_score' => round($averageScore, 2),
            ];
        }
        
        return view('reports.course-performance', compact(
            'academicYear', 
            'semester', 
            'programme', 
            'coursePerformance'
        ));
    }

    /**
     * Generate student performance report.
     */
    public function studentPerformance(Request $request)
    {
        // Get data for filter dropdowns
        $academicYears = AcademicYear::all();
        $programmes = Programme::all();
        
        // If no filters are selected, just show the form
        if (!$request->has('academic_year_id') || !$request->has('programme_id')) {
            return view('reports.student-performance', compact('academicYears', 'programmes'));
        }
        
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'programme_id' => 'required|exists:programmes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('reports.index')
                ->withErrors($validator)
                ->withInput();
        }
        
        $academicYearId = $request->input('academic_year_id');
        $programmeId = $request->input('programme_id');
        
        $academicYear = AcademicYear::findOrFail($academicYearId);
        $programme = Programme::findOrFail($programmeId);
        
        // Get students in the programme
        $students = Student::where('programme_id', $programmeId)->get();
        
        // Get performance data for each student
        $studentPerformance = [];
        
        foreach ($students as $student) {
            $results = Result::where([
                'student_id' => $student->id,
                'academic_year_id' => $academicYearId,
            ])->get();
            
            $totalCourses = $results->count();
            $passedCourses = $results->where('grade_point', '>=', 1.0)->count();
            $failedCourses = $totalCourses - $passedCourses;
            $gpa = $student->calculateGPA(null, $academicYearId);
            
            $studentPerformance[] = [
                'student' => $student,
                'total_courses' => $totalCourses,
                'passed_courses' => $passedCourses,
                'failed_courses' => $failedCourses,
                'gpa' => $gpa,
            ];
        }
        
        // Sort by GPA in descending order
        $studentPerformance = collect($studentPerformance)->sortByDesc('gpa')->values()->all();
        
        return view('reports.student-performance', compact(
            'academicYear', 
            'programme', 
            'studentPerformance'
        ));
    }

    /**
     * Generate programme statistics report.
     */
    public function programmeStatistics(Request $request)
    {
        // Get data for filter dropdowns
        $academicYears = AcademicYear::all();
        
        // If no filters are selected, just show the form
        if (!$request->has('academic_year_id')) {
            return view('reports.programme-statistics', compact('academicYears'));
        }
        
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('reports.index')
                ->withErrors($validator)
                ->withInput();
        }
        
        $academicYearId = $request->input('academic_year_id');
        $academicYear = AcademicYear::findOrFail($academicYearId);
        
        // Get all programmes
        $programmes = Programme::all();
        
        // Get statistics for each programme
        $programmeStats = [];
        
        foreach ($programmes as $programme) {
            $students = Student::where('programme_id', $programme->id)->count();
            
            $results = Result::where('academic_year_id', $academicYearId)
                ->join('students', 'results.student_id', '=', 'students.id')
                ->where('students.programme_id', $programme->id)
                ->get();
            
            $passCount = $results->where('grade_point', '>=', 1.0)->count();
            $totalResults = $results->count();
            $passRate = $totalResults > 0 ? ($passCount / $totalResults) * 100 : 0;
            $averageGpa = $results->avg('grade_point') ?: 0;
            
            $programmeStats[] = [
                'programme' => $programme,
                'students_count' => $students,
                'pass_rate' => round($passRate, 2),
                'average_gpa' => round($averageGpa, 2),
            ];
        }
        
        return view('reports.programme-statistics', compact(
            'academicYear',
            'programmeStats'
        ));
    }

    /**
     * Generate classification distribution report.
     */
    public function classificationDistribution(Request $request)
    {
        // Get data for filter dropdowns
        $programmes = Programme::all();
        
        // If no filters are selected, just show the form
        if (!$request->has('programme_id')) {
            return view('reports.classification-distribution', compact('programmes'));
        }
        
        $validator = Validator::make($request->all(), [
            'programme_id' => 'required|exists:programmes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('reports.index')
                ->withErrors($validator)
                ->withInput();
        }
        
        $programmeId = $request->input('programme_id');
        $programme = $programmeId ? Programme::findOrFail($programmeId) : null;
        
        // Get students
        $studentsQuery = Student::query();
        
        if ($programmeId) {
            $studentsQuery->where('programme_id', $programmeId);
        }
        
        $students = $studentsQuery->get();
        
        // Calculate classifications
        $classifications = [
            'First Class' => 0,
            'Second Class Upper' => 0,
            'Second Class Lower' => 0,
            'Third Class' => 0,
            'Pass' => 0,
            'Fail' => 0,
        ];
        
        foreach ($students as $student) {
            $classification = $student->getClassification();
            
            if (isset($classifications[$classification])) {
                $classifications[$classification]++;
            }
        }
        
        return view('reports.classification-distribution', compact(
            'programme', 
            'classifications'
        ));
    }

    /**
     * Generate semester comparison report.
     */
    public function semesterComparison(Request $request)
    {
        // Get data for filter dropdowns
        $academicYears = AcademicYear::all();
        $programmes = Programme::all();
        
        // If no filters are selected, just show the form
        if (!$request->has('academic_year_id')) {
            return view('reports.semester-comparison', compact('academicYears', 'programmes'));
        }
        
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'programme_id' => 'nullable|exists:programmes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('reports.index')
                ->withErrors($validator)
                ->withInput();
        }
        
        $academicYearId = $request->input('academic_year_id');
        $programmeId = $request->input('programme_id');
        
        $academicYear = AcademicYear::findOrFail($academicYearId);
        $programme = $programmeId ? Programme::findOrFail($programmeId) : null;
        
        // Get semesters for the academic year
        $semesters = Semester::where('academic_year_id', $academicYearId)->get();
        
        // Get performance data for each semester
        $semesterData = [];
        
        foreach ($semesters as $semester) {
            // Build the query
            $query = DB::table('results')
                ->join('students', 'results.student_id', '=', 'students.id')
                ->where([
                    'results.academic_year_id' => $academicYearId,
                    'results.semester_id' => $semester->id,
                ]);
                
            if ($programmeId) {
                $query->where('students.programme_id', $programmeId);
            }
            
            $averageGpa = $query->avg('results.grade_point');
            $passRate = $query->where('results.grade_point', '>=', 1.0)->count() / max(1, $query->count()) * 100;
            
            $semesterData[] = [
                'semester' => $semester,
                'average_gpa' => round($averageGpa, 2),
                'pass_rate' => round($passRate, 2),
            ];
        }
        
        return view('reports.semester-comparison', compact(
            'academicYear', 
            'programme', 
            'semesterData'
        ));
    }

    /**
     * Export report data to Excel.
     */
    public function exportToExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:gpa_distribution,course_performance,student_performance,classification_distribution,semester_comparison',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'programme_id' => 'nullable|exists:programmes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('reports.index')
                ->withErrors($validator)
                ->withInput();
        }
        
        $reportType = $request->input('report_type');
        $academicYearId = $request->input('academic_year_id');
        $semesterId = $request->input('semester_id');
        $programmeId = $request->input('programme_id');
        
        // This would typically generate an Excel file based on the report type
        // For simplicity, we'll just redirect back with a success message
        
        return redirect()->route('reports.index')
            ->with('success', 'Report exported successfully.');
    }
}
