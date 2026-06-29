<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;
use App\Models\GradeScheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BulkResultController extends Controller
{
    public function downloadTemplate($type = 'excel')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'student_id');
        $sheet->setCellValue('B1', 'grade');

        // Add sample data
        $sheet->setCellValue('A2', '1001');
        $sheet->setCellValue('A3', '1002');
        $sheet->setCellValue('A4', '1003');
        $sheet->setCellValue('B2', 'A');
        $sheet->setCellValue('B3', 'B+');
        $sheet->setCellValue('B4', 'B');

        // Style the header row
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders to all cells
        $sheet->getStyle('A1:B4')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Set the content type
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $filename = 'results_template.xlsx';

        // Create the Excel file
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        
        // Send to browser
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
    public function showUploadForm()
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        return view('results.bulk_upload', compact('academicYears'));
    }

    public function getSemesters(Request $request)
    {
        $academicYearId = $request->input('academic_year_id');
        $semesters = Semester::where('academic_year_id', $academicYearId)
            ->orderBy('name')
            ->get();
        return response()->json($semesters);
    }

    public function getCourses(Request $request)
    {
        $semesterId = $request->input('semester_id');
        $courses = Course::where('semester_id', $semesterId)
            ->orderBy('code')
            ->get();
        return response()->json($courses);
    }

    public function processUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'course_id' => 'required|exists:courses,id',
            'result_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:2048'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $file = $request->file('result_file');
            $extension = $file->getClientOriginalExtension();
            
            // Load the appropriate spreadsheet
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header row
            $header = array_shift($rows);
            
            // Clean up header row (remove any null values and trim)
            $header = array_map(function($value) {
                return strtolower(trim($value ?? ''));
            }, array_filter($header, function($value) {
                return $value !== null && trim($value) !== '';
            }));
            
            // Expected headers: student_id,grade
            $expectedHeaders = ['student_id', 'grade'];
            $missingHeaders = array_diff($expectedHeaders, $header);
            $extraHeaders = array_diff($header, $expectedHeaders);
            
            if (!empty($missingHeaders) || !empty($extraHeaders)) {
                $errorMessage = 'Invalid file format. ';
                if (!empty($missingHeaders)) {
                    $errorMessage .= 'Missing required headers: ' . implode(', ', $missingHeaders) . '. ';
                }
                if (!empty($extraHeaders)) {
                    $errorMessage .= 'Unexpected headers found: ' . implode(', ', $extraHeaders) . '. ';
                }
                $errorMessage .= 'Expected headers: student_id, grade';
                
                return redirect()->back()->with('error', $errorMessage);
            }
            
            // Find the column indexes for student_id and grade
            $studentIdIndex = array_search('student_id', $header);
            $gradeIndex = array_search('grade', $header);

            DB::beginTransaction();
            
            $processed = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Filter out empty cells but preserve indexes
                $rowData = array_filter($row, function($value) {
                    return $value !== null && trim($value) !== '';
                });
                
                // Check if we have values for both student_id and grade
                if (!isset($row[$studentIdIndex]) || !isset($row[$gradeIndex])) {
                    $errors[] = "Row " . ($index + 2) . ": Missing required columns. Please ensure both student_id and grade are provided";
                    continue;
                }

                // Get raw values first
                $rawStudentId = $row[$studentIdIndex] ?? '';
                $rawGrade = $row[$gradeIndex] ?? '';
                
                // Handle Excel number format which might come as float
                $studentId = is_numeric($rawStudentId) ? (string)intval($rawStudentId) : trim($rawStudentId);
                $grade = strtoupper(trim($rawGrade));
                
                if (empty($studentId) || empty($grade)) {
                    $errors[] = "Row " . ($index + 2) . ": Missing student ID or grade";
                    continue;
                }
                
                // Validate student ID format (alphanumeric, no spaces)
                if (!preg_match('/^[A-Z0-9]+$/', $studentId)) {
                    $errors[] = "Row " . ($index + 2) . ": Student ID must contain only letters and numbers. Current value: {$studentId}";
                    continue;
                }

                // Validate student exists using index_number
                $student = Student::where('index_number', $studentId)->first();
                if (!$student) {
                    $errors[] = "Row " . ($index + 2) . ": Student with Index Number {$studentId} not found";
                    continue;
                }

                // Get default grade scheme
                $gradeScheme = GradeScheme::getDefault();
                if (!$gradeScheme) {
                    $errors[] = "Row " . ($index + 2) . ": No default grade scheme found in the system";
                    continue;
                }

                // Find the grade in the grades table
                $validGrade = $gradeScheme->grades()->where('grade', $grade)->first();
                if (!$validGrade) {
                    $errors[] = "Row " . ($index + 2) . ": Invalid grade {$grade}. Must be a valid grade from the grade scheme";
                    continue;
                }

                // Create or update result
                Result::updateOrCreate(
                    [
                        'student_id' => $student->id, // Use actual student ID from database
                        'course_id' => $request->course_id,
                        'academic_year_id' => $request->academic_year_id,
                        'semester_id' => $request->semester_id,
                    ],
                    [
                        'grade' => $grade,
                        'grade_point' => $validGrade->gpa_value // Use gpa_value from the grades table
                    ]
                );

                $processed++;
            }

            DB::commit();

            $message = "{$processed} results processed successfully.";
            if (count($errors) > 0) {
                $message .= " Errors encountered: " . implode(", ", $errors);
                return redirect()->back()->with('warning', $message);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error processing results: ' . $e->getMessage());
        }
    }
}
