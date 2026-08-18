<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\GradeScheme;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Imports the "RESIT RESULTS TEMPLATE" workbook: index number, academic year,
 * semester, course code, Course Title, grade, is resit (Y/N) - one row per
 * student/course, with the academic year/semester/course all varying per row
 * rather than being fixed for the whole upload.
 *
 * "is resit" = Y adds a separate resit record alongside the original grade for
 * that student/course/semester/year (both remain on file; re-uploading the same
 * resit combination updates that resit record rather than duplicating it).
 * "is resit" = N overwrites the original (non-resit) record for that
 * student/course/semester/year, e.g. to correct a wrongly entered grade.
 */
class ResitResultImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    protected int $processed = 0;
    protected int $skipped = 0;
    protected array $errors = [];
    protected ?GradeScheme $gradeScheme;

    public function __construct()
    {
        $this->gradeScheme = GradeScheme::getDefault();
    }

    /**
     * Handle rows that fail the rules() validation instead of aborting the whole import.
     *
     * @param Failure[] $failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = 'Row ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            $this->skipped++;
        }
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $indexNumber = trim((string) ($row['index_number'] ?? ''));
            $academicYearName = trim((string) ($row['academic_year'] ?? ''));
            $semesterName = trim((string) ($row['semester'] ?? ''));
            $courseCode = trim((string) ($row['course_code'] ?? ''));
            $gradeRaw = strtoupper(trim((string) ($row['grade'] ?? '')));
            $isResitRaw = strtolower(trim((string) ($row['is_resit'] ?? '')));
            $isRepeated = in_array($isResitRaw, ['y', 'yes', 'true', '1'], true);

            if ($indexNumber === '' || $academicYearName === '' || $semesterName === '' || $courseCode === '' || $gradeRaw === '') {
                $this->skipped++;
                continue;
            }

            $student = Student::where('index_number', $indexNumber)->first();

            if (!$student) {
                $this->errors[] = "Student with index number {$indexNumber} not found.";
                $this->skipped++;
                continue;
            }

            $academicYear = AcademicYear::where('name', $academicYearName)->first();

            if (!$academicYear) {
                $this->errors[] = "Academic year \"{$academicYearName}\" not found (student {$indexNumber}).";
                $this->skipped++;
                continue;
            }

            $semester = $this->findSemester($academicYear->id, $semesterName);

            if (!$semester) {
                $this->errors[] = "Semester \"{$semesterName}\" not found for academic year {$academicYearName} (student {$indexNumber}).";
                $this->skipped++;
                continue;
            }

            // A course code is offered once per semester (each Course row is scoped to a
            // specific semester_id), and the same code recurs across academic years/cohorts
            // as a separate Course row each time. Matching on code alone can therefore land
            // on the wrong year's course row; matching on code + the semester already
            // resolved above picks the exact offering this result belongs to.
            $course = Course::whereRaw('UPPER(code) = ?', [strtoupper($courseCode)])
                ->where('semester_id', $semester->id)
                ->first();

            if (!$course) {
                $this->errors[] = "Course with code \"{$courseCode}\" not found for {$semesterName} {$academicYearName} (student {$indexNumber}).";
                $this->skipped++;
                continue;
            }

            $courseId = $course->id;

            if (!$this->gradeScheme) {
                $this->errors[] = 'No default grade scheme configured in the system.';
                $this->skipped++;
                continue;
            }

            if (is_numeric($gradeRaw)) {
                $score = (float) $gradeRaw;
                $gradeLetter = $this->gradeScheme->getGradeForScore($score);
                $gradePoint = $this->gradeScheme->getGradePointForScore($score);
            } else {
                $gradeRecord = $this->gradeScheme->grades()->where('grade', $gradeRaw)->first();
                $gradeLetter = $gradeRecord->grade ?? null;
                $gradePoint = $gradeRecord->gpa_value ?? null;
            }

            if ($gradeLetter === null) {
                $this->errors[] = "Invalid grade \"{$gradeRaw}\" for student {$indexNumber}, course {$courseCode}.";
                $this->skipped++;
                continue;
            }

            // is_repeated is part of the match key: a "Y" (resit) row is created/updated
            // alongside the original "N" row rather than overwriting it, so both the
            // original failing grade and the resit grade remain on record.
            Result::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'course_id' => $courseId,
                    'academic_year_id' => $academicYear->id,
                    'semester_id' => $semester->id,
                    'is_repeated' => $isRepeated,
                ],
                [
                    'grade' => $gradeLetter,
                    'grade_point' => $gradePoint,
                ]
            );

            $this->processed++;
        }
    }

    /**
     * Match a semester within an academic year by name (e.g. "First Semester"),
     * falling back to the trailing number (e.g. "Semester 2" -> semester_number 2)
     * since templates don't always use the exact name stored in the system.
     */
    protected function findSemester(int $academicYearId, string $semesterName): ?Semester
    {
        $semester = Semester::where('academic_year_id', $academicYearId)
            ->whereRaw('LOWER(name) = ?', [strtolower($semesterName)])
            ->first();

        if ($semester) {
            return $semester;
        }

        if (preg_match('/(\d+)/', $semesterName, $matches)) {
            return Semester::where('academic_year_id', $academicYearId)
                ->where('semester_number', (int) $matches[1])
                ->first();
        }

        return null;
    }

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'index_number' => 'required',
            'academic_year' => 'required',
            'semester' => 'required',
            'course_code' => 'required',
            'grade' => 'required',
        ];
    }

    /**
     * Get statistics about the import process.
     */
    public function getStats(): array
    {
        return [
            'processed' => $this->processed,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
