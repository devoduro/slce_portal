<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\GradeScheme;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UploadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $academicYears = AcademicYear::all();
        $semesters = Semester::all();
        $uploadHistory = DB::table('results')
            ->select(DB::raw('DATE(created_at) as upload_date'), DB::raw('count(*) as total_records'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('upload_date', 'desc')
            ->paginate(10);
            
        return view('uploads.index', compact('academicYears', 'semesters', 'uploadHistory'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $academicYears = AcademicYear::all();
        $semesters = Semester::all();
        return view('uploads.create', compact('academicYears', 'semesters'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('uploads.create')
                ->withErrors($validator)
                ->withInput();
        }
        
        $academicYearId = $request->input('academic_year_id');
        $semesterId = $request->input('semester_id');
        
        // Get the uploaded file
        $file = $request->file('excel_file');
        
        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Get the highest row
            $highestRow = $worksheet->getHighestRow();
            
            // Start a database transaction
            DB::beginTransaction();
            
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // Process each row starting from row 2 (assuming row 1 is header)
            for ($row = 2; $row <= $highestRow; $row++) {
                $indexNumber = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                $courseCode = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                $score = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                $isRepeated = strtolower($worksheet->getCellByColumnAndRow(4, $row)->getValue()) === 'yes';
                
                // Find the student by index number
                $student = Student::where('index_number', $indexNumber)->first();
                if (!$student) {
                    $errorCount++;
                    $errors[] = "Row {$row}: Student with index number {$indexNumber} not found.";
                    continue;
                }
                
                // Find the course by code
                $course = Course::where('code', $courseCode)->first();
                if (!$course) {
                    $errorCount++;
                    $errors[] = "Row {$row}: Course with code {$courseCode} not found.";
                    continue;
                }
                
                // Get grade information based on score
                $grade = GradeScheme::getGradeForScore($score);
                $gradePoint = GradeScheme::getGradePointForScore($score);
                $remark = GradeScheme::getRemarkForScore($score);
                
                if (!$grade || !$gradePoint) {
                    $errorCount++;
                    $errors[] = "Row {$row}: Invalid score {$score} for student {$indexNumber}.";
                    continue;
                }
                
                // Check if result already exists
                $existingResult = Result::where([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'semester_id' => $semesterId,
                    'academic_year_id' => $academicYearId,
                ])->first();
                
                if ($existingResult) {
                    // Update existing result
                    $existingResult->update([
                        'grade' => $grade,
                        'grade_point' => $gradePoint,
                        'score' => $score,
                        'remark' => $remark,
                        'is_repeated' => $isRepeated,
                    ]);
                } else {
                    // Create new result
                    Result::create([
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'semester_id' => $semesterId,
                        'academic_year_id' => $academicYearId,
                        'grade' => $grade,
                        'grade_point' => $gradePoint,
                        'score' => $score,
                        'remark' => $remark,
                        'is_repeated' => $isRepeated,
                    ]);
                }
                
                $successCount++;
            }
            
            // Commit the transaction
            DB::commit();
            
            return redirect()->route('uploads.index')
                ->with('success', "Upload completed. {$successCount} records processed successfully. {$errorCount} errors encountered.")
                ->with('errors', $errors);
                
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            
            return redirect()->route('uploads.create')
                ->with('error', 'Error processing file: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Convert the date string to a proper format
        $date = date('Y-m-d', strtotime($id));
        
        // Get all results uploaded on that date
        $results = Result::whereDate('created_at', $date)
            ->with(['student', 'course', 'semester', 'academicYear'])
            ->paginate(20);
            
        return view('uploads.show', compact('results', 'date'));
    }

    /**
     * Download a template for Excel upload.
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'Index Number');
        $sheet->setCellValue('B1', 'Course Code');
        $sheet->setCellValue('C1', 'Score');
        $sheet->setCellValue('D1', 'Is Repeated (Yes/No)');
        
        // Add some sample data
        $sheet->setCellValue('A2', 'STUDENT001');
        $sheet->setCellValue('B2', 'CSC101');
        $sheet->setCellValue('C2', '85');
        $sheet->setCellValue('D2', 'No');
        
        $sheet->setCellValue('A3', 'STUDENT002');
        $sheet->setCellValue('B3', 'MTH101');
        $sheet->setCellValue('C3', '72');
        $sheet->setCellValue('D3', 'Yes');
        
        // Create Excel file
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        
        // Save to a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'results_template');
        $writer->save($tempFile);
        
        // Return as download
        return response()->download($tempFile, 'results_upload_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Validate an Excel file before uploading.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function validateExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Get the uploaded file
        $file = $request->file('excel_file');
        
        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Get the highest row
            $highestRow = $worksheet->getHighestRow();
            
            $errors = [];
            $validRows = 0;
            
            // Check headers
            $headers = [
                $worksheet->getCellByColumnAndRow(1, 1)->getValue(),
                $worksheet->getCellByColumnAndRow(2, 1)->getValue(),
                $worksheet->getCellByColumnAndRow(3, 1)->getValue(),
                $worksheet->getCellByColumnAndRow(4, 1)->getValue(),
            ];
            
            $expectedHeaders = ['Index Number', 'Course Code', 'Score', 'Is Repeated (Yes/No)'];
            
            if ($headers !== $expectedHeaders) {
                $errors[] = 'Invalid headers. Please use the template provided.';
            }
            
            // Process each row starting from row 2 (assuming row 1 is header)
            for ($row = 2; $row <= $highestRow; $row++) {
                $indexNumber = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                $courseCode = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                $score = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                
                // Validate index number
                if (empty($indexNumber)) {
                    $errors[] = "Row {$row}: Index number is required.";
                    continue;
                }
                
                // Validate course code
                if (empty($courseCode)) {
                    $errors[] = "Row {$row}: Course code is required.";
                    continue;
                }
                
                // Validate score
                if (!is_numeric($score) || $score < 0 || $score > 100) {
                    $errors[] = "Row {$row}: Score must be a number between 0 and 100.";
                    continue;
                }
                
                $validRows++;
            }
            
            return response()->json([
                'success' => true,
                'valid_rows' => $validRows,
                'total_rows' => $highestRow - 1, // Excluding header row
                'errors' => $errors,
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Error processing file: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Delete all results uploaded on a specific date.
     *
     * @param string $id (date in Y-m-d format)
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        // Convert the date string to a proper format
        $date = date('Y-m-d', strtotime($id));
        
        // Delete all results uploaded on that date
        $count = Result::whereDate('created_at', $date)->delete();
        
        return redirect()->route('uploads.index')
            ->with('success', "{$count} results deleted successfully.");
    }
}
