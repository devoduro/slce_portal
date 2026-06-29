<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Programme;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GpaDistributionController extends Controller
{
    public function index(Request $request)
    {
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

        return view('gpa-distribution.index', [
            'students' => $studentsWithGpa,
            'programmes' => $programmes,
            'distribution' => $distribution,
            'stats' => $stats
        ]);
    }
}
