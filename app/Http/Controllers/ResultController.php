<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\GradeScheme;
use App\Models\Programme;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;
use App\Traits\ScopesToLecturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ResultController extends Controller
{
    use ScopesToLecturer;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Result::with(['student', 'course', 'academicYear', 'semester']);

        if ($this->isScopedLecturer()) {
            $query->whereIn('course_id', $this->lecturerCourseIds());
        }

        // Search by student name or index number
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->whereHas('student', function($q) use ($searchTerm) {
                $q->where('full_name', 'like', "%{$searchTerm}%")
                  ->orWhere('index_number', 'like', "%{$searchTerm}%");
            });
        }
        
        // Filter by academic year
        if ($request->has('academic_year_id') && $request->academic_year_id) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        
        // Filter by semester
        if ($request->has('semester_id') && $request->semester_id) {
            $query->where('semester_id', $request->semester_id);
        }
        
        // Filter by programme
        if ($request->has('programme_id') && $request->programme_id) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('programme_id', $request->programme_id);
            });
        }
        
        // Filter by course
        if ($request->has('course_id') && $request->course_id) {
            $query->where('course_id', $request->course_id);
        }
        
        // Filter by grade range
        if ($request->has('min_score') && $request->min_score) {
            $query->where('total_score', '>=', $request->min_score);
        }
        if ($request->has('max_score') && $request->max_score) {
            $query->where('total_score', '<=', $request->max_score);
        }
        
        $results = $query->orderBy('created_at', 'desc')
                        ->paginate(20)
                        ->withQueryString();
        
        // Get data for filter dropdowns
        $academicYears = AcademicYear::all();
        $semesters = Semester::all();
        $programmes = Programme::all();
        $courses = Course::all();
        
        return view('results.index', compact(
            'results', 
            'academicYears', 
            'semesters', 
            'programmes', 
            'courses'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $students = Student::orderBy('full_name')->get();
        $courses = $this->scopeToLecturer(Course::orderBy('code'))->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $semesters = Semester::all();
        $gradeSchemes = GradeScheme::all();
        
        return view('results.create', compact(
            'students', 
            'courses', 
            'academicYears', 
            'semesters',
            'gradeSchemes'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'course_id' => 'required|exists:courses,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'score' => 'required|numeric|min:0|max:100',
            'assessment_score' => 'nullable|numeric|min:0|max:40',
            'exam_score' => 'nullable|numeric|min:0|max:60',
        ]);

        if ($validator->fails()) {
            return redirect()->route('results.create')
                ->withErrors($validator)
                ->withInput();
        }

        if ($this->isScopedLecturer() && !in_array((int) $request->input('course_id'), $this->lecturerCourseIds())) {
            return redirect()->route('results.create')
                ->withErrors(['course_id' => 'You can only enter results for courses assigned to you.'])
                ->withInput();
        }

        // Check if result already exists
        $existingResult = Result::where([
            'student_id' => $request->input('student_id'),
            'course_id' => $request->input('course_id'),
            'academic_year_id' => $request->input('academic_year_id'),
            'semester_id' => $request->input('semester_id'),
        ])->first();
        
        if ($existingResult) {
            return redirect()->route('results.create')
                ->withErrors(['general' => 'Result already exists for this student, course, academic year, and semester.'])
                ->withInput();
        }
        
        // Calculate grade and grade point based on score
        $score = $request->input('score');
        $grade = GradeScheme::getGrade($score);
        $gradePoint = GradeScheme::getGradePoint($score);
        $remark = GradeScheme::getRemark($score);
        
        // Create result
        Result::create([
            'student_id' => $request->input('student_id'),
            'course_id' => $request->input('course_id'),
            'academic_year_id' => $request->input('academic_year_id'),
            'semester_id' => $request->input('semester_id'),
            'score' => $score,
            'grade' => $grade,
            'grade_point' => $gradePoint,
            'remark' => $remark,
        ]);
        
        return redirect()->route('results.index')
            ->with('success', 'Result created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Result $result)
    {
        return view('results.show', compact('result'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $result = Result::findOrFail($id);
        $students = Student::orderBy('full_name')->get();
        $courses = Course::orderBy('code')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $semesters = Semester::all();
        $gradeSchemes = GradeScheme::all();
        
        return view('results.edit', compact(
            'result',
            'students', 
            'courses', 
            'academicYears', 
            'semesters',
            'gradeSchemes'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $result = Result::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'course_id' => 'required|exists:courses,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'score' => 'required|numeric|min:0|max:100',
            'assessment_score' => 'nullable|numeric|min:0|max:40',
            'exam_score' => 'nullable|numeric|min:0|max:60',
        ]);

        if ($validator->fails()) {
            return redirect()->route('results.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }
        
        // Check if result already exists (excluding this one)
        $existingResult = Result::where([
            'student_id' => $request->input('student_id'),
            'course_id' => $request->input('course_id'),
            'academic_year_id' => $request->input('academic_year_id'),
            'semester_id' => $request->input('semester_id'),
        ])
        ->where('id', '!=', $id)
        ->first();
        
        if ($existingResult) {
            return redirect()->route('results.edit', $id)
                ->withErrors(['general' => 'Result already exists for this student, course, academic year, and semester.'])
                ->withInput();
        }
        
        // Calculate grade and grade point based on score
        $score = $request->input('score');
        $grade = GradeScheme::getGrade($score);
        $gradePoint = GradeScheme::getGradePoint($score);
        $remark = GradeScheme::getRemark($score);
        
        // Update result
        $result->update([
            'student_id' => $request->input('student_id'),
            'course_id' => $request->input('course_id'),
            'academic_year_id' => $request->input('academic_year_id'),
            'semester_id' => $request->input('semester_id'),
            'score' => $score,
            'grade' => $grade,
            'grade_point' => $gradePoint,
            'remark' => $remark,
            'assessment_score' => $request->input('assessment_score'),
            'exam_score' => $request->input('exam_score'),
        ]);
        
        return redirect()->route('results.index')
            ->with('success', 'Result updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $result = Result::findOrFail($id);
        $result->delete();
        
        return redirect()->route('results.index')
            ->with('success', 'Result deleted successfully.');
    }
    
    /**
     * Search results.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        $results = Result::with(['student', 'course', 'academicYear', 'semester'])
            ->whereHas('student', function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('full_name', 'like', "%{$query}%")
                  ->orWhere('student_id', 'like', "%{$query}%");
            })
            ->orWhereHas('course', function($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('title', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('results.index', compact('results', 'query'));
    }
    
    /**
     * Filter results by academic year.
     */
    public function filterByAcademicYear(Request $request)
    {
        $academicYearId = $request->input('academic_year_id');
        
        $results = Result::with(['student', 'course', 'academicYear', 'semester'])
            ->where('academic_year_id', $academicYearId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        $academicYear = AcademicYear::findOrFail($academicYearId);
            
        return view('results.index', compact('results', 'academicYear'));
    }
    
    /**
     * Filter results by semester.
     */
    public function filterBySemester(Request $request)
    {
        $semesterId = $request->input('semester_id');
        
        $results = Result::with(['student', 'course', 'academicYear', 'semester'])
            ->where('semester_id', $semesterId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        $semester = Semester::findOrFail($semesterId);
            
        return view('results.index', compact('results', 'semester'));
    }
    
    /**
     * Filter results by student.
     */
    public function filterByStudent(Request $request)
    {
        $studentId = $request->input('student_id');
        
        $results = Result::with(['student', 'course', 'academicYear', 'semester'])
            ->where('student_id', $studentId)
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('semester_id', 'asc')
            ->paginate(20);
            
        $student = Student::findOrFail($studentId);
            
        return view('results.index', compact('results', 'student'));
    }
    
    /**
     * Filter results by course.
     */
    public function filterByCourse(Request $request)
    {
        $courseId = $request->input('course_id');
        
        $results = Result::with(['student', 'course', 'academicYear', 'semester'])
            ->where('course_id', $courseId)
            ->orderBy('score', 'desc')
            ->paginate(20);
            
        $course = Course::findOrFail($courseId);
            
        return view('results.index', compact('results', 'course'));
    }
    
    /**
     * Bulk create results.
     */
    public function bulkCreate()
    {
        $courses = $this->scopeToLecturer(Course::orderBy('code'))->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $semesters = Semester::all();

        return view('results.bulk_create', compact('courses', 'academicYears', 'semesters'));
    }
    
    /**
     * Download template for bulk results upload.
     */
    public function downloadTemplate()
    {
        // Check if PhpSpreadsheet is available, if not fallback to CSV
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            return $this->downloadCsvTemplate();
        }
        
        // Create Excel file using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Results Template');
        
        // Set column headers with formatting
        $sheet->setCellValue('A1', 'index_number');
        $sheet->setCellValue('B1', 'grade');
        $sheet->setCellValue('C1', 'score');
        
        // Format header row
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A1:C1')->getFill()->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:C1')->getFont()->getColor()->setRGB('FFFFFF');
        
        // Add sample data
        $sheet->setCellValue('A2', 'STU12345');
        $sheet->setCellValue('B2', 'A');
        $sheet->setCellValue('C2', '90');
        
        $sheet->setCellValue('A3', 'STU67890');
        $sheet->setCellValue('B3', 'B+');
        $sheet->setCellValue('C3', '78');
        
        $sheet->setCellValue('A4', 'STU24680');
        $sheet->setCellValue('B4', '');
        $sheet->setCellValue('C4', '75');
        
        // Format data rows
        $sheet->getStyle('A2:C4')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A2:C4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        
        // Add instructions
        $sheet->setCellValue('A6', 'Instructions:');
        $sheet->getStyle('A6')->getFont()->setBold(true);
        $sheet->getStyle('A6')->getFont()->setSize(12);
        
        $sheet->setCellValue('A7', '1. index_number: Student index number (required)');
        $sheet->setCellValue('A8', '2. grade: Either a letter grade (A, B+, etc.) or leave empty if providing score (optional if score is provided)');
        $sheet->setCellValue('A9', '3. score: Total score (0-100) (required if grade is empty).');
        
        // Add grading scheme information
        $sheet->setCellValue('A12', 'Grading Scheme:');
        $sheet->getStyle('A12')->getFont()->setBold(true);
        $sheet->getStyle('A12')->getFont()->setSize(12);
        
        $sheet->setCellValue('A13', 'Grade');
        $sheet->setCellValue('B13', 'Score Range');
        $sheet->setCellValue('C13', 'Grade Point');
        $sheet->getStyle('A13:C13')->getFont()->setBold(true);
        $sheet->getStyle('A13:C13')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A13:C13')->getFill()->getStartColor()->setRGB('D9D9D9');
        
        $grades = [
            ['A', '80-100', '4.0'],
            ['B+', '75-79', '3.5'],
            ['B', '70-74', '3.0'],
            ['C+', '65-69', '2.5'],
            ['C', '60-64', '2.0'],
            ['D+', '55-59', '1.5'],
            ['D', '50-54', '1.0'],
            ['F', '0-49', '0.0'],
        ];
        
        $row = 14;
        foreach ($grades as $grade) {
            $sheet->setCellValue('A' . $row, $grade[0]);
            $sheet->setCellValue('B' . $row, $grade[1]);
            $sheet->setCellValue('C' . $row, $grade[2]);
            $row++;
        }
        
        $sheet->getStyle('A13:C' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Auto-size columns
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'results_template_' . date('Y-m-d') . '.xlsx';
        $tempPath = storage_path('app/temp/' . $filename);
        
        // Ensure the temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $writer->save($tempPath);
        
        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
    
    /**
     * Fallback method to download CSV template if PhpSpreadsheet is not available
     */
    private function downloadCsvTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="results_template.csv"',
        ];
        
        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['index_number', 'grade', 'score']);
            
            // Add sample data
            fputcsv($file, ['STU12345', 'A', '90']);
            fputcsv($file, ['STU67890', 'B+', '78']);
            fputcsv($file, ['STU24680', '', '75']);
            
            // Add notes
            fputcsv($file, []);
            fputcsv($file, ['Notes:']);
            fputcsv($file, ['1. index_number: Student index number (required)']);
            fputcsv($file, ['2. grade: Either a letter grade (A, B+, etc.) or a numeric score (0-100) (required)']);
            fputcsv($file, ['3. score: Total score (0-100) (required)']);
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Store bulk results.
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        if ($this->isScopedLecturer() && !in_array((int) $request->input('course_id'), $this->lecturerCourseIds())) {
            return redirect()->back()
                ->withErrors(['course_id' => 'You can only enter results for courses assigned to you.'])
                ->withInput();
        }

        $courseId = $request->input('course_id');
        $academicYearId = $request->input('academic_year_id');
        $semesterId = $request->input('semester_id');

        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Process the uploaded file
            $file = $request->file('file');
            $path = $file->getRealPath();
            $extension = strtolower($file->getClientOriginalExtension());
            $processed = 0;
            $skipped = 0;
            $errors = [];
            
            // Check if the file is Excel or CSV
            if (in_array($extension, ['xlsx', 'xls'])) {
                // Handle Excel file
                if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                    throw new \Exception('PhpSpreadsheet library is not available. Please upload a CSV file instead.');
                }
                
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
                $spreadsheet = $reader->load($path);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                // Get headers from first row
                $header = array_map('trim', $rows[0]);
                
                // Process data rows
                $data = array_slice($rows, 1);
                
                // Continue with processing like CSV
                $this->processResultsData($header, $data, $courseId, $academicYearId, $semesterId, $processed, $skipped, $errors);
            } 
            elseif (($handle = fopen($path, 'r')) !== false) {
                // Read the header row
                $header = array_map('trim', fgetcsv($handle, 1000, ','));
                
                // Collect all data rows
                $data = [];
                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    $data[] = $row;
                }
                
                fclose($handle);
                
                // Process the data
                $this->processResultsData($header, $data, $courseId, $academicYearId, $semesterId, $processed, $skipped, $errors);
            } else {
                throw new \Exception('Unsupported file format. Please upload a CSV or Excel file.');
            }
            
            DB::commit();
            
            $message = "Successfully processed {$processed} results.";
            
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} rows.";
            }
            
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', array_slice($errors, 0, 3));
                
                if (count($errors) > 3) {
                    $message .= " and " . (count($errors) - 3) . " more.";
                }
            }
            
            return redirect()->route('results.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('results.bulk-create')
                ->withErrors(['general' => 'An error occurred: ' . $e->getMessage()])
                ->withInput();
        }
    }
    
    /**
     * Process results data from uploaded file
     */
    private function processResultsData(array $header, array $data, $courseId, $academicYearId, $semesterId, &$processed, &$skipped, &$errors)
    {
        // Debug: Log the headers found in the file
        \Log::info('File Headers found: ' . implode(', ', $header));
        
        // Convert headers to lowercase and trim whitespace for case-insensitive matching
        $headerLower = array_map(function($item) {
            return strtolower(trim($item));
        }, $header);
        
        // Debug: Log the processed headers
        \Log::info('Processed headers: ' . implode(', ', $headerLower));
        
        // Map column indices (case-insensitive)
        $indexNumberIdx = array_search('index_number', $headerLower);
        if ($indexNumberIdx === false) {
            // Try alternative column names
            $possibleIndexColumns = ['indexnumber', 'student_id', 'studentid', 'id', 'student', 'index', 'student index', 'student_index', 'studentindex'];
            
            foreach ($possibleIndexColumns as $colName) {
                $indexNumberIdx = array_search($colName, $headerLower);
                if ($indexNumberIdx !== false) {
                    break;
                }
            }
            
            // If still not found, try partial matching
            if ($indexNumberIdx === false) {
                foreach ($headerLower as $idx => $colName) {
                    if (strpos($colName, 'index') !== false || strpos($colName, 'student') !== false) {
                        $indexNumberIdx = $idx;
                        break;
                    }
                }
            }
        }
        
        $gradeIdx = array_search('grade', $headerLower);
        $scoreIdx = array_search('score', $headerLower);
        if ($scoreIdx === false) {
            // Try alternative column names for score
            $possibleScoreColumns = ['marks', 'total', 'total_score', 'mark', 'points', 'result'];
            
            foreach ($possibleScoreColumns as $colName) {
                $scoreIdx = array_search($colName, $headerLower);
                if ($scoreIdx !== false) {
                    break;
                }
            }
        }
        
        // Check if required columns exist
        if ($indexNumberIdx === false) {
            throw new \Exception('File must contain a column for student index number. Found columns: ' . implode(', ', $header));
        }
        
        // Debug: Log which column was identified as the index number
        \Log::info('Using column "' . $header[$indexNumberIdx] . '" (index ' . $indexNumberIdx . ') as student index number');
        
        if ($gradeIdx === false && $scoreIdx === false) {
            throw new \Exception('File must contain either a grade or score column');
        }
        
        if ($scoreIdx !== false) {
            \Log::info('Using column "' . $header[$scoreIdx] . '" (index ' . $scoreIdx . ') as score');
        }
        
        if ($gradeIdx !== false) {
            \Log::info('Using column "' . $header[$gradeIdx] . '" (index ' . $gradeIdx . ') as grade');
        }
        
        // Process each row
        foreach ($data as $rowIndex => $row) {
            // Skip empty rows
            if (!isset($row[$indexNumberIdx]) || empty($row[$indexNumberIdx]) || 
                (is_string($row[$indexNumberIdx]) && strpos($row[$indexNumberIdx], 'Note') !== false)) {
                $skipped++;
                continue;
            }
            
            // Find student by index number
            $indexNumber = trim($row[$indexNumberIdx]);
            $student = Student::where('index_number', $indexNumber)->first();
            
            if (!$student) {
                $errors[] = "Student with index number {$indexNumber} not found (row " . ($rowIndex + 2) . ").";
                $skipped++;
                continue;
            }
            
            // Parse score from either direct score or grade
            $score = null;
            
            // If score column exists and has a value, use it directly
            if ($scoreIdx !== false && isset($row[$scoreIdx]) && !empty($row[$scoreIdx]) && is_numeric($row[$scoreIdx])) {
                $score = (float) $row[$scoreIdx];
            }
            // Otherwise try to get score from grade
            elseif ($gradeIdx !== false && isset($row[$gradeIdx]) && !empty($row[$gradeIdx])) {
                // If grade is numeric, use it directly
                if (is_numeric($row[$gradeIdx])) {
                    $score = (float) $row[$gradeIdx];
                } else {
                    // Convert letter grade to score
                    $letterGrade = strtoupper(trim($row[$gradeIdx]));
                    $score = GradeScheme::getScoreFromGrade($letterGrade);
                    
                    if ($score === null) {
                        $errors[] = "Invalid grade format for student {$indexNumber}: {$row[$gradeIdx]} (row " . ($rowIndex + 2) . ").";
                        $skipped++;
                        continue;
                    }
                }
            } else {
                $errors[] = "No grade or score provided for student {$indexNumber} (row " . ($rowIndex + 2) . ").";
                $skipped++;
                continue;
            }
            
            // Get grade details
            $grade = GradeScheme::getGrade($score);
            $gradePoint = GradeScheme::getGradePoint($score);
            $remark = GradeScheme::getRemark($score);
            
            // Check if result already exists
            $existingResult = Result::where([
                'student_id' => $student->id,
                'course_id' => $courseId,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
            ])->first();
            
            if ($existingResult) {
                // Update existing result
                $existingResult->update([
                    'score' => $score,
                    'grade' => $grade,
                    'grade_point' => $gradePoint,
                    'remark' => $remark,
                ]);
            } else {
                // Create new result
                Result::create([
                    'student_id' => $student->id,
                    'course_id' => $courseId,
                    'academic_year_id' => $academicYearId,
                    'semester_id' => $semesterId,
                    'score' => $score,
                    'grade' => $grade,
                    'grade_point' => $gradePoint,
                    'remark' => $remark,
                ]);
            }
            
            $processed++;
        }
    }
}
