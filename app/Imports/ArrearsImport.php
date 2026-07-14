<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentArrear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class ArrearsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
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
            $referenceNumber = trim((string) ($row['reference_number'] ?? ''));
            $academicYearName = trim((string) ($row['academic_year'] ?? ''));
            $amount = $row['amount'] ?? null;

            if ($referenceNumber === '' || $academicYearName === '' || $amount === null || $amount === '') {
                $this->skipped++;
                continue;
            }

            $student = Student::where('reference_number', $referenceNumber)->first();

            if (!$student) {
                $this->errors[] = "Student with reference number {$referenceNumber} not found.";
                $this->skipped++;
                continue;
            }

            $academicYear = AcademicYear::where('name', $academicYearName)->first();

            if (!$academicYear) {
                $this->errors[] = "Academic year \"{$academicYearName}\" not found for student with reference number {$referenceNumber}.";
                $this->skipped++;
                continue;
            }

            // Negative amounts are valid here: they represent the school owing the student
            // (e.g. an overpayment) rather than the student owing the school.
            if (!is_numeric($amount) || (float) $amount == 0.0) {
                $this->errors[] = "Invalid amount for student with reference number {$referenceNumber} ({$academicYearName}).";
                $this->skipped++;
                continue;
            }

            StudentArrear::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
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
            'reference_number' => 'required',
            'academic_year' => 'required',
            // Negative amounts are allowed (school owes the student, e.g. an overpayment);
            // only zero is meaningless here.
            'amount' => ['required', 'numeric', 'not_in:0'],
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
