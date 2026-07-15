<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\StudentFeeCharge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class StudentFeeChargeImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    protected int $processed = 0;
    protected int $skipped = 0;
    protected array $errors = [];

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
            $category = trim((string) ($row['category'] ?? ''));
            $amount = $row['amount'] ?? null;
            $academicYearName = trim((string) ($row['academic_year'] ?? ''));

            if ($indexNumber === '' || $category === '' || $amount === null || $amount === '' || $academicYearName === '') {
                $this->skipped++;
                continue;
            }

            $student = Student::where('index_number', $indexNumber)->first();

            if (!$student) {
                $this->errors[] = "Student with index number {$indexNumber} not found.";
                $this->skipped++;
                continue;
            }

            if (!array_key_exists($category, FeeCategory::options())) {
                $this->errors[] = "Unknown category \"{$category}\" for student {$indexNumber} (expected one of: " . implode(', ', array_keys(FeeCategory::options())) . ').';
                $this->skipped++;
                continue;
            }

            $academicYear = AcademicYear::where('name', $academicYearName)->first();

            if (!$academicYear) {
                $this->errors[] = "Academic year \"{$academicYearName}\" not found for student {$indexNumber}.";
                $this->skipped++;
                continue;
            }

            // Zero and negative amounts are both allowed: zero can represent a waived/void
            // charge still worth recording, and a negative amount represents a credit/
            // reduction against that category rather than an additional charge.
            if (!is_numeric($amount)) {
                $this->errors[] = "Invalid amount for student {$indexNumber}.";
                $this->skipped++;
                continue;
            }

            StudentFeeCharge::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'category' => $category,
                ],
                [
                    'amount' => (float) $amount,
                    'notes' => $row['notes'] ?? null,
                    'recorded_by' => Auth::id(),
                ]
            );

            $this->processed++;
        }
    }

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'index_number' => 'required',
            'category' => 'required',
            // Zero and negative amounts are valid (see the note in collection()); only a
            // genuinely non-numeric value is rejected.
            'amount' => 'required|numeric',
            'academic_year' => 'required',
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
