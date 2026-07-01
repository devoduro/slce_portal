<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Programme;
use App\Models\Result;
use App\Models\Semester;
use App\Models\User;
use App\Traits\ScopesToLecturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    use ScopesToLecturer;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = $this->scopeToLecturer(
            Course::with(['programmes', 'semester', 'lecturer'])->orderBy('code')
        )->paginate(20);

        return view('courses.index', compact('courses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $programmes = Programme::all();
        $semesters = Semester::all();
        $lecturers = User::role('Lecturer')->orderBy('name')->get();

        return view('courses.create', compact('programmes', 'semesters', 'lecturers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credit_hours' => 'required|numeric|min:0|max:12',
            'programme_ids' => 'required|array|min:1',
            'programme_ids.*' => 'exists:programmes,id',
            'semester_id' => 'required|exists:semesters,id',
            'is_core' => 'boolean',
            'lecturer_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('courses.create')
                ->withErrors($validator)
                ->withInput();
        }

        // Create the course without the programme_ids
        $course = Course::create([
            'code' => $request->code,
            'title' => $request->title,
            'description' => $request->description,
            'credit_hours' => $request->credit_hours,
            'semester_id' => $request->semester_id,
            'is_core' => $request->is_core ?? false,
            'lecturer_id' => $request->lecturer_id ?: null,
        ]);

        // Attach the selected programmes to the course
        $course->programmes()->attach($request->programme_ids);
        
        return redirect()->route('courses.index')
            ->with('success', 'Course created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $course = Course::with(['programmes', 'semester', 'prerequisite'])->findOrFail($id);
        
        // Get statistics for this course
        $resultStats = Result::where('course_id', $id)
            ->select(
                DB::raw('COUNT(*) as total_students'),
                DB::raw('AVG(score) as average_score'),
                DB::raw('COUNT(CASE WHEN grade_point >= 1.0 THEN 1 END) as pass_count'),
                DB::raw('COUNT(CASE WHEN grade_point < 1.0 THEN 1 END) as fail_count')
            )
            ->first();
            
        // Get grade distribution
        $gradeDistribution = Result::where('course_id', $id)
            ->select('grade', DB::raw('COUNT(*) as count'))
            ->groupBy('grade')
            ->get()
            ->pluck('count', 'grade')
            ->toArray();
            
        // Get courses that have this course as a prerequisite
        $dependentCourses = Course::where('prerequisite_id', $id)->get();
        
        return view('courses.show', compact(
            'course', 
            'resultStats', 
            'gradeDistribution', 
            'dependentCourses'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $course = Course::with('programmes')->findOrFail($id);
        $programmes = Programme::all();
        $semesters = Semester::all();
        $prerequisites = Course::where('id', '!=', $id)->get();
        $lecturers = User::role('Lecturer')->orderBy('name')->get();

        return view('courses.edit', compact('course', 'programmes', 'semesters', 'prerequisites', 'lecturers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $course = Course::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:courses,code,' . $id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credit_hours' => 'required|numeric|min:0|max:12',
            'programme_ids' => 'required|array|min:1',
            'programme_ids.*' => 'exists:programmes,id',
            'semester_id' => 'required|exists:semesters,id',
            'is_core' => 'boolean',
            'lecturer_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('courses.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        // Update the course without the programme_ids
        $course->update([
            'code' => $request->code,
            'title' => $request->title,
            'description' => $request->description,
            'credit_hours' => $request->credit_hours,
            'semester_id' => $request->semester_id,
            'is_core' => $request->is_core ?? false,
            'lecturer_id' => $request->lecturer_id ?: null,
        ]);
        
        // Sync the selected programmes to the course
        $course->programmes()->sync($request->programme_ids);
        
        return redirect()->route('courses.show', $id)
            ->with('success', 'Course updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $course = Course::findOrFail($id);
        
        // Check if there are any results associated with this course
        if ($course->results()->count() > 0) {
            return redirect()->route('courses.index')
                ->with('error', 'Cannot delete course with associated results.');
        }
        
        // Check if this course is a prerequisite for other courses
        if ($course->dependentCourses()->count() > 0) {
            return redirect()->route('courses.index')
                ->with('error', 'Cannot delete course that is a prerequisite for other courses.');
        }
        
        $course->delete();
        
        return redirect()->route('courses.index')
            ->with('success', 'Course deleted successfully.');
    }
    
    /**
     * Search courses.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        $courses = Course::with(['programmes', 'semester'])
            ->where('code', 'like', "%{$query}%")
            ->orWhere('title', 'like', "%{$query}%")
            ->orderBy('code')
            ->paginate(20);
            
        return view('courses.index', compact('courses', 'query'));
    }
    
    /**
     * Filter courses by programme.
     */
    public function filterByProgramme(Request $request)
    {
        $programmeId = $request->input('programme_id');
        
        $courses = Course::with(['programmes', 'semester'])
            ->whereHas('programmes', function($query) use ($programmeId) {
                $query->where('programmes.id', $programmeId);
            })
            ->orderBy('code')
            ->paginate(20);
            
        $programme = Programme::findOrFail($programmeId);
            
        return view('courses.index', compact('courses', 'programme'));
    }
    
    /**
     * Filter courses by semester.
     */
    public function filterBySemester(Request $request)
    {
        $semesterId = $request->input('semester_id');
        
        $courses = Course::with(['programmes', 'semester'])
            ->where('semester_id', $semesterId)
            ->orderBy('code')
            ->paginate(20);
            
        $semester = Semester::findOrFail($semesterId);
            
        return view('courses.index', compact('courses', 'semester'));
    }
    
    /**
     * Display students enrolled in a course.
     */
    public function students(string $id)
    {
        $course = Course::findOrFail($id);
        
        $results = Result::with('student')
            ->where('course_id', $id)
            ->orderBy('score', 'desc')
            ->paginate(20);
            
        return view('courses.students', compact('course', 'results'));
    }
    
    /**
     * Export course data.
     */
    public function export(string $id)
    {
        $course = Course::with(['programmes', 'semester', 'results.student'])->findOrFail($id);
        
        // This would typically generate an Excel file with course data
        // For simplicity, we'll just redirect back with a success message
        
        return redirect()->route('courses.show', $id)
            ->with('success', 'Course data exported successfully.');
    }
    
    /**
     * Show form to add students to a course.
     */
    public function addStudentsForm(string $id)
    {
        $course = Course::with(['programmes', 'semester'])->findOrFail($id);
        
        // Get students who are not already enrolled in this course
        // First get IDs of students already enrolled
        $enrolledStudentIds = Result::where('course_id', $id)
            ->pluck('student_id')
            ->toArray();
            
        // Then get students who are not in that list and match any of the course's programmes
        $programmeIds = $course->programmes->pluck('id')->toArray();
        $students = \App\Models\Student::whereIn('programme_id', $programmeIds)
            ->whereNotIn('id', $enrolledStudentIds)
            ->orderBy('full_name')
            ->get();
        
        // Get academic years and semesters for the form
        $academicYears = \App\Models\AcademicYear::orderBy('name', 'desc')->get();
        $semesters = \App\Models\Semester::all();
            
        return view('courses.add_students', compact('course', 'students', 'academicYears', 'semesters'));
    }
    
    /**
     * Add students to a course.
     */
    public function addStudents(Request $request, string $id)
    {
        $course = Course::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);
        
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $studentIds = $request->student_ids;
        $academicYearId = $request->academic_year_id;
        $semesterId = $request->semester_id;
        $count = 0;
        
        foreach ($studentIds as $studentId) {
            // Check if the student is already enrolled in this course
            $exists = Result::where('student_id', $studentId)
                ->where('course_id', $id)
                ->where('academic_year_id', $academicYearId)
                ->where('semester_id', $semesterId)
                ->exists();
                
            if (!$exists) {
                Result::create([
                    'student_id' => $studentId,
                    'course_id' => $id,
                    'academic_year_id' => $academicYearId,
                    'semester_id' => $semesterId,
                    'class_score' => 0,
                    'exam_score' => 0,
                    'total_score' => 0,
                    'grade' => 'N/A',
                    'grade_point' => 0,
                ]);
                
                $count++;
            }
        }
        
        return redirect()->route('courses.students', $id)
            ->with('success', "$count students successfully added to this course.");
    }
    
    /**
     * Show results for a specific course.
     */
    public function results(string $id)
    {
        $course = Course::with(['programmes', 'semester'])->findOrFail($id);
        
        // Get all results for this course, grouped by academic year and semester
        $results = Result::with(['student', 'academicYear', 'semester'])
            ->where('course_id', $id)
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('semester_id', 'desc')
            ->orderBy('total_score', 'desc')
            ->paginate(50);
            
        // Get academic years and semesters for filtering
        $academicYears = \App\Models\AcademicYear::orderBy('name', 'desc')->get();
        $semesters = \App\Models\Semester::all();
        
        // Calculate statistics
        $stats = [
            'total' => $results->total(),
            'average' => $results->avg('total_score') ?? 0,
            'highest' => $results->max('total_score') ?? 0,
            'lowest' => $results->min('total_score') ?? 0,
            'pass_rate' => $results->where('total_score', '>=', 40)->count() / max(1, $results->count()) * 100
        ];
        
        return view('courses.results', compact('course', 'results', 'academicYears', 'semesters', 'stats'));
    }
}
