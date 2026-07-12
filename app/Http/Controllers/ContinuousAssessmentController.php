<?php

namespace App\Http\Controllers;

use App\Exports\CaCoursesExport;
use App\Models\CaScoreSetting;
use App\Models\Course;
use App\Models\ContinuousAssessment;
use App\Models\Programme;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\Student;
use App\Services\AttendanceScoreCalculator;
use App\Traits\ScopesToLecturer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class ContinuousAssessmentController extends Controller
{
    use ScopesToLecturer;

    /**
     * List the courses the authenticated user can enter CA scores for.
     */
    public function index(Request $request)
    {
        $courses = $this->buildCourseRows($request);

        $programmes = Programme::orderBy('name')->get();
        $semesters = Semester::orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();

        return view('continuous-assessment.index', compact('courses', 'programmes', 'semesters'));
    }

    /**
     * Export the current filtered course list to Excel.
     */
    public function exportExcel(Request $request)
    {
        $courses = $this->buildCourseRows($request);

        return Excel::download(new CaCoursesExport($courses), 'continuous-assessment-courses.xlsx');
    }

    /**
     * Export the current filtered course list to PDF.
     */
    public function exportPdf(Request $request)
    {
        $courses = $this->buildCourseRows($request);

        $pdf = PDF::loadView('continuous-assessment.export-pdf', compact('courses'))->setPaper('a4', 'landscape');

        return $pdf->download('continuous-assessment-courses.pdf');
    }

    /**
     * Build the filtered course list shared by the on-screen index and both exports.
     */
    protected function buildCourseRows(Request $request)
    {
        $query = $this->scopeToLecturer(
            Course::with(['semester', 'lecturers', 'programmes'])->orderBy('code')
        );

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('programme_id')) {
            $query->whereHas('programmes', fn ($q) => $q->where('programmes.id', $request->programme_id));
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        return $query->get();
    }

    /**
     * Show the CA roster for a single course.
     */
    public function show(Course $course)
    {
        if ($this->isScopedLecturer() && !$course->lecturers()->where('lecturers.id', $this->authLecturerId())->exists()) {
            abort(403);
        }

        $semester = $course->semester;

        $registrations = Registration::where('course_id', $course->id)
            ->where('status', 'registered')
            ->with('student')
            ->get()
            ->filter(fn ($registration) => $registration->student !== null);

        $rows = $registrations->map(function ($registration) use ($course, $semester) {
            $student = $registration->student;
            $setting = CaScoreSetting::where('level', $student->level)->first();

            $ca = ContinuousAssessment::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->where('semester_id', $semester?->id)
                ->where('academic_year_id', $registration->academic_year_id)
                ->first();

            $attendanceScore = $semester ? AttendanceScoreCalculator::score($student, $course, $semester) : 0;

            $total = $attendanceScore
                + (float) ($ca->project_score ?? 0)
                + (float) ($ca->assignment_score ?? 0)
                + (float) ($ca->mid_semester_score ?? 0);

            return [
                'student' => $student,
                'ca' => $ca,
                'setting' => $setting,
                'attendance_score' => $attendanceScore,
                'total' => $total,
            ];
        })->sortBy(fn ($row) => $row['student']->full_name);

        return view('continuous-assessment.show', compact('course', 'semester', 'rows'));
    }

    /**
     * Save the submitted CA scores for a course's roster.
     */
    public function store(Request $request, Course $course)
    {
        if ($this->isScopedLecturer() && !$course->lecturers()->where('lecturers.id', $this->authLecturerId())->exists()) {
            abort(403);
        }

        $semester = $course->semester;
        $scores = $request->input('scores', []);
        $errors = [];

        foreach ($scores as $studentId => $values) {
            $student = Student::find($studentId);
            $registration = Registration::where('course_id', $course->id)
                ->where('student_id', $studentId)
                ->where('status', 'registered')
                ->first();

            if (!$student || !$registration) {
                continue;
            }

            $setting = CaScoreSetting::where('level', $student->level)->first();
            $data = [];

            foreach ([
                'project' => 'project_score',
                'assignment' => 'assignment_score',
                'mid_semester' => 'mid_semester_score',
            ] as $inputKey => $column) {
                if (!isset($values[$inputKey]) || $values[$inputKey] === '') {
                    continue;
                }

                $value = (float) $values[$inputKey];
                $maxColumn = $inputKey === 'mid_semester' ? 'mid_semester_max' : "{$inputKey}_max";
                $max = $setting?->{$maxColumn};

                if ($value < 0 || ($max !== null && $value > (float) $max)) {
                    $errors[] = "{$student->full_name}: {$inputKey} score must be between 0 and " . ($max ?? 'N/A') . '.';
                    continue;
                }

                $data[$column] = $value;
            }

            if (!empty($data)) {
                ContinuousAssessment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'semester_id' => $semester?->id,
                        'academic_year_id' => $registration->academic_year_id,
                    ],
                    $data
                );
            }
        }

        if (!empty($errors)) {
            return redirect()->route('continuous-assessment.show', $course)
                ->withErrors(['scores' => $errors])
                ->withInput();
        }

        return redirect()->route('continuous-assessment.show', $course)
            ->with('success', 'CA scores saved successfully.');
    }
}
