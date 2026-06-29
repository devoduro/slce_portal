<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PDF;

class TranscriptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get all programmes for the filter dropdown
        $programmes = \App\Models\Programme::orderBy('name')->get();
        
        // Get all academic years for the filter dropdown
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        
        // Build the query with filters
        $query = Student::with(['programme', 'results'])
            ->when($request->filled('search'), function ($q) use ($request) {
                return $q->where(function($query) use ($request) {
                    $search = '%' . $request->search . '%';
                    $query->where('index_number', 'like', $search)
                          ->orWhere('full_name', 'like', $search)
                          ->orWhere('email', 'like', $search);
                });
            })
            ->when($request->filled('programme'), function ($q) use ($request) {
                return $q->where('programme_id', $request->programme);
            })
            ->when($request->filled('academic_year'), function ($q) use ($request) {
                return $q->whereHas('results', function($query) use ($request) {
                    $query->where('academic_year_id', $request->academic_year);
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                return $q->where('status', $request->status);
            });
        
        // Execute the query with pagination
        $students = $query->orderBy('index_number')->paginate(10)->withQueryString();
        
        return view('transcripts.index', compact('students', 'programmes', 'academicYears'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $students = Student::with('programme')->get();
        $academicYears = AcademicYear::all();
        return view('transcripts.create', compact('students', 'academicYears'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('transcripts.create')
                ->withErrors($validator)
                ->withInput();
        }

        $studentId = $request->input('student_id');
        $academicYearId = $request->input('academic_year_id');
        
        // Redirect to the generate method with the selected parameters
        return redirect()->route('transcripts.generate', [
            'student' => $studentId,
        ])->with('academic_year_id', $academicYearId);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $student = Student::with(['programme', 'results' => function($query) {
            $query->with(['course', 'semester', 'academicYear'])
                  ->orderBy('academic_year_id')
                  ->orderBy('semester_id');
        }])->findOrFail($id);
        
        return view('transcripts.show', compact('student'));
    }

    /**
     * Generate a PDF transcript for the specified student.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function generate(Request $request, string $student)
    {
        $studentId = $student;
        $academicYearId = $request->input('academic_year_id') ?? session('academic_year_id');
        
        $student = Student::with(['programme'])->findOrFail($studentId);
        
        // Get results filtered by academic year if specified
        $resultsQuery = $student->results()->with(['course', 'semester', 'academicYear'])
                               ->orderBy('academic_year_id')
                               ->orderBy('semester_id');
        
        if ($academicYearId) {
            $resultsQuery->where('academic_year_id', $academicYearId);
        }
        
        $results = $resultsQuery->get();
        
        // Group results by academic year and semester
        $groupedResults = [];
        foreach ($results as $result) {
            $academicYearId = $result->academic_year_id;
            $semesterId = $result->semester_id;
            
            if (!isset($groupedResults[$academicYearId])) {
                $groupedResults[$academicYearId] = [
                    'academic_year' => $result->academicYear,
                    'semesters' => []
                ];
            }
            
            if (!isset($groupedResults[$academicYearId]['semesters'][$semesterId])) {
                $groupedResults[$academicYearId]['semesters'][$semesterId] = [
                    'semester' => $result->semester,
                    'results' => [],
                    'gpa' => 0
                ];
            }
            
            $groupedResults[$academicYearId]['semesters'][$semesterId]['results'][] = $result;
        }
        
        // Calculate GPA for each semester
        foreach ($groupedResults as &$academicYear) {
            foreach ($academicYear['semesters'] as $semesterId => &$semesterData) {
                $semesterData['gpa'] = $student->calculateGPA($semesterId);
            }
        }
        
        // Sort grouped results by academic year name (ascending order - oldest first, most recent last)
        usort($groupedResults, function($a, $b) {
            return strcmp($a['academic_year']->name, $b['academic_year']->name);
        });
        
        $cgpa = $student->calculateCGPA();
        $classification = $student->getClassification();
        
        // Get institution settings from DB
        $institutionSettings = DB::table('settings')->where('category', 'institution')->get();
        
        // Convert settings collection to associative array for easier access in the view
        $settings = [];
        foreach ($institutionSettings as $setting) {
            $settings[$setting->key] = $setting->value;
        }
        
        // Generate PDF
        $pdf = PDF::loadView('transcripts.pdf', compact(
            'student', 
            'groupedResults', 
            'cgpa', 
            'classification',
            'settings'
        ));
        
        // Set PDF options
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
        ]);
        
        // Generate a filename
        $filename = 'transcript_' . $student->index_number . '.pdf';
        
        // Return the PDF for download
        return $pdf->download($filename);
    }

    /**
     * Bulk generate transcripts for multiple students.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function bulk(Request $request)
    {
        // Show bulk generation form
        $students = Student::with('programme')->get();
        $academicYears = AcademicYear::all();
        return view('transcripts.bulk', compact('students', 'academicYears'));
    }
    
    /**
     * Preview a transcript before generating PDF.
     *
     * @param Request $request
     * @param string $student Student ID
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request, string $student)
    {
        $studentId = $student;
        $academicYearId = $request->input('academic_year_id');
        
        $student = Student::with(['programme'])->findOrFail($studentId);
        
        // Get results filtered by academic year if specified
        $resultsQuery = $student->results()->with(['course', 'semester', 'academicYear'])
                               ->orderBy('academic_year_id')
                               ->orderBy('semester_id');
        
        if ($academicYearId) {
            $resultsQuery->where('academic_year_id', $academicYearId);
        }
        
        $results = $resultsQuery->get();
        
        // Group results by academic year and semester
        $groupedResults = [];
        foreach ($results as $result) {
            $academicYearId = $result->academic_year_id;
            $semesterId = $result->semester_id;
            
            if (!isset($groupedResults[$academicYearId])) {
                $groupedResults[$academicYearId] = [
                    'academic_year' => $result->academicYear,
                    'semesters' => []
                ];
            }
            
            if (!isset($groupedResults[$academicYearId]['semesters'][$semesterId])) {
                $groupedResults[$academicYearId]['semesters'][$semesterId] = [
                    'semester' => $result->semester,
                    'results' => [],
                    'gpa' => 0
                ];
            }
            
            $groupedResults[$academicYearId]['semesters'][$semesterId]['results'][] = $result;
        }
        
        // Calculate GPA for each semester
        foreach ($groupedResults as &$academicYear) {
            foreach ($academicYear['semesters'] as $semesterId => &$semesterData) {
                $semesterData['gpa'] = $student->calculateGPA($semesterId);
            }
        }
        
        // Sort grouped results by academic year name (ascending order - oldest first, most recent last)
        usort($groupedResults, function($a, $b) {
            return strcmp($a['academic_year']->name, $b['academic_year']->name);
        });
        
        $cgpa = $student->calculateCGPA();
        $classification = $student->getClassification();
        
        // Get institution settings from DB
        $institutionSettings = DB::table('settings')->where('category', 'institution')->get();
        
        // Convert settings collection to associative array for easier access in the view
        $settings = [];
        foreach ($institutionSettings as $setting) {
            $settings[$setting->key] = $setting->value;
        }
        
        // Define options for the view
        $options = [
            'type' => 'Official',
            'include_comments' => false
        ];
        
        return view('transcripts.preview', compact(
            'student', 
            'groupedResults', 
            'cgpa', 
            'classification',
            'academicYearId',
            'settings',
            'options'
        ));
    }

    /**
     * Bulk generate transcripts for multiple students.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function bulkGenerate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('transcripts.index')
                ->withErrors($validator)
                ->withInput();
        }

        $studentIds = $request->input('student_ids');
        $academicYearId = $request->input('academic_year_id');
        
        // If only one student is selected, redirect to single transcript generation
        if (count($studentIds) === 1) {
            return redirect()->route('transcripts.generate', [
                'student' => $studentIds[0],
                'academic_year_id' => $academicYearId
            ]);
        }
        
        // For multiple students, create a ZIP file containing all transcripts
        $zipFileName = 'transcripts_' . date('Y-m-d_H-i-s') . '.zip';
        $zipFilePath = storage_path('app/public/temp/' . $zipFileName);
        
        // Create temp directory if it doesn't exist
        if (!file_exists(storage_path('app/public/temp'))) {
            mkdir(storage_path('app/public/temp'), 0755, true);
        }
        
        // Create new ZIP archive
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return redirect()->route('transcripts.index')
                ->with('error', 'Could not create ZIP file');
        }
        
        // Generate PDFs for each student and add to ZIP
        foreach ($studentIds as $studentId) {
            $student = Student::with(['programme'])->findOrFail($studentId);
            
            // Get results filtered by academic year if specified
            $resultsQuery = $student->results()->with(['course', 'semester', 'academicYear'])
                                   ->orderBy('academic_year_id')
                                   ->orderBy('semester_id');
            
            if ($academicYearId) {
                $resultsQuery->where('academic_year_id', $academicYearId);
            }
            
            $results = $resultsQuery->get();
            
            // Skip if no results found
            if ($results->isEmpty()) {
                continue;
            }
            
            // Group results by academic year and semester
            $groupedResults = [];
            foreach ($results as $result) {
                $academicYearId = $result->academic_year_id;
                $semesterId = $result->semester_id;
                
                if (!isset($groupedResults[$academicYearId])) {
                    $groupedResults[$academicYearId] = [
                        'academic_year' => $result->academicYear,
                        'semesters' => []
                    ];
                }
                
                if (!isset($groupedResults[$academicYearId]['semesters'][$semesterId])) {
                    $groupedResults[$academicYearId]['semesters'][$semesterId] = [
                        'semester' => $result->semester,
                        'results' => [],
                        'gpa' => 0
                    ];
                }
                
                $groupedResults[$academicYearId]['semesters'][$semesterId]['results'][] = $result;
            }
            
            // Calculate GPA for each semester
            foreach ($groupedResults as &$academicYear) {
                foreach ($academicYear['semesters'] as $semesterId => &$semesterData) {
                    $semesterData['gpa'] = $student->calculateGPA($semesterId);
                }
            }
            
            // Sort grouped results by academic year name (ascending order - oldest first, most recent last)
            usort($groupedResults, function($a, $b) {
                return strcmp($a['academic_year']->name, $b['academic_year']->name);
            });
            
            $cgpa = $student->calculateCGPA();
            $classification = $student->getClassification();
            
            // Get institution settings from DB
            $institutionSettings = DB::table('settings')->where('category', 'institution')->get();
            
            // Convert settings collection to associative array for easier access in the view
            $settings = [];
            foreach ($institutionSettings as $setting) {
                $settings[$setting->key] = $setting->value;
            }
            
            // Generate PDF
            $pdf = PDF::loadView('transcripts.pdf', compact(
                'student', 
                'groupedResults', 
                'cgpa', 
                'classification',
                'settings'
            ));
            
            // Set PDF options
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
            ]);
            
            // Generate a filename
            $filename = 'transcript_' . $student->index_number . '.pdf';
            $tempPath = storage_path('app/public/temp/' . $filename);
            
            // Save PDF to temp file
            $pdf->save($tempPath);
            
            // Add to ZIP
            $zip->addFile($tempPath, $filename);
        }
        
        $zip->close();
        
        // Clean up temp PDF files
        foreach ($studentIds as $studentId) {
            $student = Student::find($studentId);
            if ($student) {
                $tempFile = storage_path('app/public/temp/transcript_' . $student->index_number . '.pdf');
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        }
        
        // Return ZIP file for download
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }
}
