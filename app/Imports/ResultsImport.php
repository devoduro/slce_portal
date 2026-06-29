<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\GradeScheme;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ResultsImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $academicYearId;
    protected $semesterId;
    protected $courseId;
    protected $course;
    protected $processed = 0;
    protected $skipped = 0;
    protected $errors = [];

    public function __construct($academicYearId, $semesterId, $courseId)
    {
        $this->academicYearId = $academicYearId;
        $this->semesterId = $semesterId;
        $this->courseId = $courseId;
        $this->course = Course::find($courseId);
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Skip empty rows
            if (empty($row['index_number']) || empty($row['grade'])) {
                $this->skipped++;
                continue;
            }

            // Find student by index number
            $student = Student::where('index_number', $row['index_number'])->first();
            
            if (!$student) {
                $this->errors[] = "Student with index number {$row['index_number']} not found.";
                $this->skipped++;
                continue;
            }

            // Convert letter grade to score if needed
            $score = $this->parseScore($row);
            
            if ($score === null) {
                $this->errors[] = "Invalid grade format for student {$row['index_number']}: {$row['grade']}";
                $this->skipped++;
                continue;
            }

            // Get grade details
            $grade = GradeScheme::getGrade($score);
            $gradePoint = GradeScheme::getGradePoint($score);
            $remark = GradeScheme::getRemark($score);

            // Get assessment and exam scores if available
            $assessmentScore = isset($row['assessment_score']) ? $row['assessment_score'] : null;
            $examScore = isset($row['exam_score']) ? $row['exam_score'] : null;

            // Check if result already exists
            $existingResult = Result::where([
                'student_id' => $student->id,
                'course_id' => $this->courseId,
                'academic_year_id' => $this->academicYearId,
                'semester_id' => $this->semesterId,
            ])->first();

            if ($existingResult) {
                // Update existing result
                $existingResult->update([
                    'score' => $score,
                    'grade' => $grade,
                    'grade_point' => $gradePoint,
                    'remark' => $remark,
                    'assessment_score' => $assessmentScore,
                    'exam_score' => $examScore,
                ]);
            } else {
                // Create new result
                Result::create([
                    'student_id' => $student->id,
                    'course_id' => $this->courseId,
                    'academic_year_id' => $this->academicYearId,
                    'semester_id' => $this->semesterId,
                    'score' => $score,
                    'grade' => $grade,
                    'grade_point' => $gradePoint,
                    'remark' => $remark,
                    'assessment_score' => $assessmentScore,
                    'exam_score' => $examScore,
                ]);
            }

            $this->processed++;
        }
    }

    /**
     * Parse score from row data
     * 
     * @param array $row
     * @return float|null
     */
    protected function parseScore($row)
    {
        try {
            // If numeric score is provided directly
            if (isset($row['score']) && is_numeric($row['score'])) {
                return (float) $row['score'];
            }
            
            // If grade is provided as a letter or number
            if (isset($row['grade'])) {
                if (is_numeric($row['grade'])) {
                    return (float) $row['grade'];
                }
                
                // Convert letter grade to score using GradeScheme
                $letterGrade = strtoupper(trim($row['grade']));
                $score = GradeScheme::getScoreFromGrade($letterGrade);
                
                // If no matching grade found, try to use assessment and exam scores
                if ($score === null && isset($row['assessment_score']) && isset($row['exam_score'])) {
                    if (is_numeric($row['assessment_score']) && is_numeric($row['exam_score'])) {
                        return (float) $row['assessment_score'] + (float) $row['exam_score'];
                    }
                }
                
                return $score;
            }
            
            return null;
        } catch (\Exception $e) {
            // Log error and return null
            \Log::error('Error parsing score: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get validation rules
     * 
     * @return array
     */
    public function rules(): array
    {
        return [
            'index_number' => 'required',
            'grade' => 'required',
            'assessment_score' => 'nullable|numeric|min:0|max:40',
            'exam_score' => 'nullable|numeric|min:0|max:60',
        ];
    }

    /**
     * Get statistics about the import process
     * 
     * @return array
     */
    public function getStats(): array
    {
        return [
            'processed' => $this->processed,
            'skipped' => $this->skipped,
            'errors' => $this->errors
        ];
    }
}
