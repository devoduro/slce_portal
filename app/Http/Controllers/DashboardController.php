<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Result;
use App\Models\Student;
use App\Models\Course;
use App\Models\Semester;
use App\Models\Programme;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }
    /**
     * Display the dashboard with key metrics and analytics.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $timeRange = $request->input('time_range', 'all');
        $programmeId = $request->input('programme_id');
        
        // Date filters based on time range
        $dateFilter = null;
        if ($timeRange !== 'all') {
            $now = Carbon::now();
            switch ($timeRange) {
                case 'week':
                    $dateFilter = $now->subDays(7);
                    break;
                case 'month':
                    $dateFilter = $now->subDays(30);
                    break;
                case 'quarter':
                    $dateFilter = $now->subMonths(3);
                    break;
                case 'year':
                    $dateFilter = $now->subYear();
                    break;
            }
        }
        
        // Get current academic year and semester
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();
        $currentSemester = Semester::where('is_current', true)->first();
        
        // Calculate academic year progress percentage
        $academicYearProgress = 0;
        if ($currentAcademicYear) {
            $startDate = Carbon::parse($currentAcademicYear->start_date);
            $endDate = Carbon::parse($currentAcademicYear->end_date);
            $today = Carbon::now();
            
            if ($today->lt($startDate)) {
                $academicYearProgress = 0;
            } elseif ($today->gt($endDate)) {
                $academicYearProgress = 100;
            } else {
                $totalDays = $startDate->diffInDays($endDate);
                $daysPassed = $startDate->diffInDays($today);
                $academicYearProgress = round(($daysPassed / $totalDays) * 100);
            }
        }
        
        // Calculate semester progress percentage
        $semesterProgress = 0;
        if ($currentSemester) {
            $startDate = Carbon::parse($currentSemester->start_date);
            $endDate = Carbon::parse($currentSemester->end_date);
            $today = Carbon::now();
            
            if ($today->lt($startDate)) {
                $semesterProgress = 0;
            } elseif ($today->gt($endDate)) {
                $semesterProgress = 100;
            } else {
                $totalDays = $startDate->diffInDays($endDate);
                $daysPassed = $startDate->diffInDays($today);
                $semesterProgress = round(($daysPassed / $totalDays) * 100);
            }
        }
        
        // Get total number of students with growth percentage
        $studentsQuery = Student::query();
        if ($programmeId) {
            $studentsQuery->where('programme_id', $programmeId);
        }
        $totalStudents = $studentsQuery->count();
        
        // Calculate student growth percentage
        $lastMonthStudents = Student::where('created_at', '<', Carbon::now()->subMonth())->count();
        $studentGrowth = $lastMonthStudents > 0 ? 
            round((($totalStudents - $lastMonthStudents) / $lastMonthStudents) * 100, 1) : 0;
        
        // Get total courses with growth percentage
        $coursesQuery = Course::query();
        if ($programmeId) {
            $coursesQuery->where('programme_id', $programmeId);
        }
        $totalCourses = $coursesQuery->count();
        
        // Calculate course growth
        $lastMonthCourses = Course::where('created_at', '<', Carbon::now()->subMonth())->count();
        $courseGrowth = $lastMonthCourses > 0 ? 
            round((($totalCourses - $lastMonthCourses) / $lastMonthCourses) * 100, 1) : 0;

        // Get total transcripts with growth percentage
        $transcriptsQuery = Result::query();
        if ($dateFilter) {
            $transcriptsQuery->where('created_at', '>=', $dateFilter);
        }
        if ($programmeId) {
            $transcriptsQuery->whereHas('student', function ($query) use ($programmeId) {
                $query->where('programme_id', $programmeId);
            });
        }
        $totalTranscripts = $transcriptsQuery->count();
        
        // Calculate transcript growth
        $lastMonthTranscripts = Result::where('created_at', '<', Carbon::now()->subMonth())->count();
        $transcriptGrowth = $lastMonthTranscripts > 0 ? 
            round((($totalTranscripts - $lastMonthTranscripts) / $lastMonthTranscripts) * 100, 1) : 0;
        
        // Get GPA distribution data with proper ranges
        $gpaRanges = [
            '0.0-0.9' => [0.0, 0.9],
            '1.0-1.9' => [1.0, 1.9],
            '2.0-2.9' => [2.0, 2.9],
            '3.0-3.9' => [3.0, 3.9],
            '4.0' => [4.0, 4.0]
        ];
        
        $gpaDistribution = [];
        $gpaQuery = DB::table('results');
        if ($programmeId) {
            $gpaQuery->join('students', 'results.student_id', '=', 'students.id')
                ->where('students.programme_id', $programmeId);
        }
        
        foreach ($gpaRanges as $range => $bounds) {
            if ($range === '4.0') {
                $count = $gpaQuery->clone()->where('grade_point', '=', 4.0)->count();
            } else {
                $count = $gpaQuery->clone()->whereBetween('grade_point', $bounds)->count();
            }
            $gpaDistribution[$range] = $count;
        }
        
        // Get GPA averages by year with optional programme filter
        $gpaAveragesQuery = DB::table('results')
            ->join('courses', 'results.course_id', '=', 'courses.id')
            ->join('students', 'results.student_id', '=', 'students.id');
            
        if ($programmeId) {
            $gpaAveragesQuery->where('students.programme_id', $programmeId);
        }
        
        $gpaAverages = $gpaAveragesQuery
            ->select(
                'results.academic_year_id',
                DB::raw('AVG(results.grade_point) as average_gpa'),
                DB::raw('SUM(courses.credit_hours) as total_credit_hours')
            )
            ->groupBy('results.academic_year_id')
            ->get()
            ->map(function ($item) {
                $academicYear = AcademicYear::find($item->academic_year_id);
                return [
                    'year' => $academicYear ? $academicYear->name : 'Unknown',
                    'average_gpa' => round($item->average_gpa, 2),
                    'total_credit_hours' => $item->total_credit_hours
                ];
            });
        
        // Get performance trend data (monthly average GPA for the past year)
        $performanceTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->format('M');
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();
            
            $query = DB::table('results')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                
            if ($programmeId) {
                $query->join('students', 'results.student_id', '=', 'students.id')
                    ->where('students.programme_id', $programmeId);
            }
            
            $avgGpa = $query->avg('grade_point') ?: 0;
            $passRate = $query->where('grade_point', '>=', 2.0)->count() / 
                        ($query->count() ?: 1) * 100;
            
            $performanceTrend[] = [
                'month' => $monthName,
                'avg_gpa' => round($avgGpa, 2),
                'pass_rate' => round($passRate, 1)
            ];
        }
        
        // Get top 5 performing students with optional programme filter
        $studentsQuery = Student::with(['results.course', 'programme']);
        
        if ($programmeId) {
            $studentsQuery->where('programme_id', $programmeId);
        }
        
        $topStudents = $studentsQuery->get()
            ->map(function ($student) {
                $cgpa = $student->calculateCGPA();
                return [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'email' => $student->email,
                    'index_number' => $student->index_number,
                    'cgpa' => $cgpa,
                    'programme' => $student->programme
                ];
            })
            ->sortByDesc('cgpa')
            ->take(5)
            ->values();
        
        // Get recent activities (latest 5 results added) with more details
        $recentActivitiesQuery = Result::with(['student', 'course', 'semester', 'academicYear']);
        
        if ($dateFilter) {
            $recentActivitiesQuery->where('created_at', '>=', $dateFilter);
        }
        
        if ($programmeId) {
            $recentActivitiesQuery->whereHas('student', function ($query) use ($programmeId) {
                $query->where('programme_id', $programmeId);
            });
        }
        
        $recentActivities = $recentActivitiesQuery
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($result) {
                $activityType = 'grade';
                if ($result->created_at->diffInHours() < 24) {
                    $activityType = 'registration';
                }
                
                $studentName = $result->student ? $result->student->full_name : 'Unknown';  
                $courseName = $result->course ? $result->course->name : 'Unknown Course';
                $courseCode = $result->course ? $result->course->code : 'Unknown';
                $grade = $result->grade;
                
                // Create a descriptive message based on activity type
                $message = '';
                if ($activityType === 'registration') {
                    $message = "$studentName registered for $courseName ($courseCode)";
                } else { // grade
                    $message = "$studentName received grade $grade in $courseName ($courseCode)";
                }
                
                return [
                    'id' => $result->id,
                    'student_name' => $studentName,
                    'course_code' => $courseCode,
                    'description' => $courseName,
                    'grade' => $grade,
                    'created_at' => $result->created_at->diffForHumans(),
                    'type' => $activityType,
                    'message' => $message,
                    'user' => $studentName,
                    'time' => $result->created_at->diffForHumans()
                ];
            });
        
        // Get all programmes for filter dropdown
        $programmes = Programme::all();
        
        // Get total number of courses with optional programme filter
        $coursesQuery = Course::query();
        if ($programmeId) {
            $coursesQuery->where('programme_id', $programmeId);
        }
        $totalCourses = $coursesQuery->count();
        
        // Calculate average GPA across all results with optional filters
        $averageGpaQuery = Result::query();
        if ($dateFilter) {
            $averageGpaQuery->where('created_at', '>=', $dateFilter);
        }
        if ($programmeId) {
            $averageGpaQuery->whereHas('student', function ($query) use ($programmeId) {
                $query->where('programme_id', $programmeId);
            });
        }
        $averageGPA = $averageGpaQuery->avg('grade_point') ?: 0;

        // Calculate GPA distribution with proper ranges
        $gpaRanges = [
            '0.00-0.99' => ['min' => 0, 'max' => 0.99],
            '1.00-1.99' => ['min' => 1, 'max' => 1.99],
            '2.00-2.99' => ['min' => 2, 'max' => 2.99],
            '3.00-3.99' => ['min' => 3, 'max' => 3.99],
            '4.00-4.00' => ['min' => 4, 'max' => 4]
        ];

        // Initialize distribution array with zeros
        $gpaDistribution = array_fill_keys(array_keys($gpaRanges), 0);

        // Get all students with their results
        $students = Student::with('results.course')->get();

        foreach ($students as $student) {
            // Calculate CGPA for each student
            $totalPoints = 0;
            $totalCredits = 0;
            
            foreach ($student->results as $result) {
                if ($result->course) {
                    $totalPoints += $result->grade_point * $result->course->credit_hours;
                    $totalCredits += $result->course->credit_hours;
                }
            }
            
            $cgpa = $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0;
            // Increment the appropriate range counter
            foreach ($gpaRanges as $range => $limits) {
                if ($cgpa >= $limits['min'] && $cgpa <= $limits['max']) {
                    $gpaDistribution[$range]++;
                    break;
                }
            }
        }



         // Get all programmes for filter dropdown
         $programmes = Programme::all();
        
         // Build the base query for students
         $query = Student::query()
             ->select('students.*', 'programmes.name as programme_name')
             ->join('programmes', 'students.programme_id', '=', 'programmes.id');
             
         // Apply programme filter
         if ($request->filled('programme_id')) {
             $query->where('programme_id', $request->programme_id);
         }
 
         // Get students with their results
         $students = $query->get();
 
         // Calculate CGPA for each student
         $studentsWithGpa = $students->map(function($student) {
             // Get all results grouped by academic year and semester
             $results = Result::where('student_id', $student->id)
                 ->join('courses', 'results.course_id', '=', 'courses.id')
                 ->join('semesters', 'results.semester_id', '=', 'semesters.id')
                 ->join('academic_years', 'semesters.academic_year_id', '=', 'academic_years.id')
                 ->select(
                     'results.*',
                     'courses.credit_hours',
                     'academic_years.name as academic_year',
                     'semesters.name as semester',
                     'semesters.semester_number'
                 )
                 ->orderBy('academic_years.name')
                 ->orderBy('semesters.semester_number')
                 ->get();
             
             $totalPoints = 0;
             $totalCredits = 0;
             $semesterGpas = [];
             
             // Group results by academic year and semester
             $groupedResults = $results->groupBy(function($result) {
                 return $result->academic_year . ' - ' . $result->semester;
             });
             
             // Calculate GPA for each semester
             foreach ($groupedResults as $period => $periodResults) {
                 $semesterPoints = 0;
                 $semesterCredits = 0;
                 
                 foreach ($periodResults as $result) {
                     $semesterPoints += $result->grade_point * $result->credit_hours;
                     $semesterCredits += $result->credit_hours;
                 }
                 
                 if ($semesterCredits > 0) {
                     $semesterGpas[] = [
                         'period' => $period,
                         'gpa' => $semesterPoints / $semesterCredits,
                         'credits' => $semesterCredits
                     ];
                 }
                 
                 $totalPoints += $semesterPoints;
                 $totalCredits += $semesterCredits;
             }
             
             // Calculate CGPA
             $cgpa = $totalCredits > 0 ? $totalPoints / $totalCredits : 0;
             $student->calculated_gpa = round($cgpa, 2);
             $student->semester_gpas = $semesterGpas;
             
             return $student;
         });
 
         // Apply GPA filters
         if ($request->filled('min_gpa')) {
             $studentsWithGpa = $studentsWithGpa->filter(function($student) use ($request) {
                 return $student->calculated_gpa >= $request->min_gpa;
             });
         }
         
         if ($request->filled('max_gpa')) {
             $studentsWithGpa = $studentsWithGpa->filter(function($student) use ($request) {
                 return $student->calculated_gpa <= $request->max_gpa;
             });
         }
 
         // Calculate GPA distribution
         $distribution = [
             '0.00-0.99' => 0,
             '1.00-1.99' => 0,
             '2.00-2.99' => 0,
             '3.00-3.99' => 0,
             '4.00-4.00' => 0
         ];
 
         foreach ($studentsWithGpa as $student) {
             $gpa = (float)$student->calculated_gpa;
             
             if ($gpa >= 0 && $gpa < 1) {
                 $distribution['0.00-0.99']++;
             } elseif ($gpa >= 1 && $gpa < 2) {
                 $distribution['1.00-1.99']++;
             } elseif ($gpa >= 2 && $gpa < 3) {
                 $distribution['2.00-2.99']++;
             } elseif ($gpa >= 3 && $gpa < 4) {
                 $distribution['3.00-3.99']++;
             } elseif ($gpa == 4) {
                 $distribution['4.00-4.00']++;
             }
         }
 
         // Calculate statistics
         $stats = [
             'total_students' => $studentsWithGpa->count(),
             'average_gpa' => $studentsWithGpa->avg('calculated_gpa'),
             'highest_gpa' => $studentsWithGpa->max('calculated_gpa'),
             'lowest_gpa' => $studentsWithGpa->min('calculated_gpa')
         ];
 
       
 
        
        return view('dashboard', [
            'totalStudents' => $totalStudents,
            'totalCourses' => $totalCourses,
            'averageGPA' => $averageGPA,
            'currentAcademicYear' => $currentAcademicYear,
            'currentSemester' => $currentSemester,
            'academicYearProgress' => $academicYearProgress,
            'semesterProgress' => $semesterProgress,
            'gpaDistribution' => $gpaDistribution,
            'programmes' => $programmes,
            'recentActivities' => $recentActivities ?? [],
            'topStudents' => $topStudents ?? [],
            'timeRange' => $timeRange,
            'programmeId' => $programmeId
        ]);
    }
}
