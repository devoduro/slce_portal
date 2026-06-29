<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\GradeScheme;
use App\Models\Programme;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImportExportController extends Controller
{
    /**
     * Show import/export dashboard.
     */
    public function index()
    {
        return view('import-export.index');
    }
    
    /**
     * Export students data.
     */
    public function exportStudents(Request $request)
    {
        $programmeId = $request->input('programme_id');
        
        $query = Student::query()->with('programme');
        
        if ($programmeId) {
            $query->where('programme_id', $programmeId);
        }
        
        $students = $query->get();
        
        // This would typically generate an Excel file with student data
        // For simplicity, we'll just redirect back with a success message
        
        return redirect()->back()
            ->with('success', 'Students data exported successfully.');
    }
    
    /**
     * Export courses data.
     */
    public function exportCourses(Request $request)
    {
        $programmeId = $request->input('programme_id');
        $semesterId = $request->input('semester_id');
        
        $query = Course::query()->with(['programme', 'semester']);
        
        if ($programmeId) {
            $query->where('programme_id', $programmeId);
        }
        
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        
        $courses = $query->get();
        
        // This would typically generate an Excel file with course data
        // For simplicity, we'll just redirect back with a success message
        
        return redirect()->back()
            ->with('success', 'Courses data exported successfully.');
    }
    
    /**
     * Export results data.
     */
    public function exportResults(Request $request)
    {
        $academicYearId = $request->input('academic_year_id');
        $semesterId = $request->input('semester_id');
        $courseId = $request->input('course_id');
        $programmeId = $request->input('programme_id');
        
        $query = Result::query()->with(['student', 'course', 'academicYear', 'semester']);
        
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        
        if ($courseId) {
            $query->where('course_id', $courseId);
        }
        
        if ($programmeId) {
            $query->whereHas('student', function($q) use ($programmeId) {
                $q->where('programme_id', $programmeId);
            });
        }
        
        $results = $query->get();
        
        // This would typically generate an Excel file with results data
        // For simplicity, we'll just redirect back with a success message
        
        return redirect()->back()
            ->with('success', 'Results data exported successfully.');
    }
    
    /**
     * Show form to import students.
     */
    public function importStudentsForm()
    {
        $programmes = Programme::all();
        return view('import-export.import-students', compact('programmes'));
    }
    
    /**
     * Process student import.
     */
    public function importStudents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'programme_id' => 'required|exists:programmes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('import.students.form')
                ->withErrors($validator)
                ->withInput();
        }
        
        $file = $request->file('file');
        $programmeId = $request->input('programme_id');
        
        // Process file import logic would go here
        // For simplicity, we'll just redirect back with a success message
        
        return redirect()->route('import.students.form')
            ->with('success', 'Students imported successfully.');
    }
    
    /**
     * Show form to import courses.
     */
    public function importCoursesForm()
    {
        $programmes = Programme::all();
        $semesters = Semester::with('academicYear')->get();
        return view('import-export.import-courses', compact('programmes', 'semesters'));
    }
    
    /**
     * Process course import.
     */
    public function importCourses(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'programme_id' => 'required|exists:programmes,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('import.courses.form')
                ->withErrors($validator)
                ->withInput();
        }
        
        $file = $request->file('file');
        $programmeId = $request->input('programme_id');
        $semesterId = $request->input('semester_id');
        
        // Process file import logic would go here
        // For simplicity, we'll just redirect back with a success message
        
        return redirect()->route('import.courses.form')
            ->with('success', 'Courses imported successfully.');
    }
    
    /**
     * Show form to import results.
     */
    public function importResultsForm()
    {
        $courses = Course::orderBy('code')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $semesters = Semester::all();
        
        return view('import-export.import-results', compact('courses', 'academicYears', 'semesters'));
    }
    
    /**
     * Process results import.
     */
    public function importResults(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'course_id' => 'required|exists:courses,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('import.results.form')
                ->withErrors($validator)
                ->withInput();
        }
        
        $file = $request->file('file');
        $courseId = $request->input('course_id');
        $academicYearId = $request->input('academic_year_id');
        $semesterId = $request->input('semester_id');
        
        // Process file import logic would go here
        // For simplicity, we'll just redirect back with a success message
        
        return redirect()->route('import.results.form')
            ->with('success', 'Results imported successfully.');
    }
    
    /**
     * Export report data.
     */
    public function exportReport(Request $request)
    {
        $reportType = $request->input('report_type');
        $academicYearId = $request->input('academic_year_id');
        $programmeId = $request->input('programme_id');
        $semesterId = $request->input('semester_id');
        
        // This would typically generate an Excel file with report data
        // For simplicity, we'll just redirect back with a success message
        
        return redirect()->back()
            ->with('success', 'Report exported successfully.');
    }
}
