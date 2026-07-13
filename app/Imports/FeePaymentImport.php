<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class FeePaymentImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
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
            $amount = $row['amount'] ?? null;
            $rawDate = $row['date'] ?? null;
            $academicYearName = trim((string) ($row['academic_year'] ?? ''));
            $bankReference = trim((string) ($row['bank_reference'] ?? ''));

            if ($indexNumber === '' || $amount === null || $amount === '' || $rawDate === null || $rawDate === '' || $academicYearName === '') {
                $this->skipped++;
                continue;
            }

            $student = Student::where('index_number', $indexNumber)->first();

            if (!$student) {
                $this->errors[] = "Student with index number {$indexNumber} not found.";
                $this->skipped++;
                continue;
            }

            if (!is_numeric($amount) || (float) $amount <= 0) {
                $this->errors[] = "Invalid amount for student {$indexNumber}.";
                $this->skipped++;
                continue;
            }

            $paymentDate = $this->parseDate($rawDate);

            if (!$paymentDate) {
                $this->errors[] = "Invalid date for student {$indexNumber} (\"{$rawDate}\").";
                $this->skipped++;
                continue;
            }

            $academicYear = AcademicYear::where('name', $academicYearName)->first();

            if (!$academicYear) {
                $this->errors[] = "Academic year \"{$academicYearName}\" not found for student {$indexNumber}.";
                $this->skipped++;
                continue;
            }

            $student->payments()->create([
                'academic_year_id' => $academicYear->id,
                'amount' => (float) $amount,
                'payment_method' => 'other',
                'payment_date' => $paymentDate,
                'reference_number' => $bankReference !== '' ? $bankReference : null,
                'notes' => 'Bulk uploaded',
                'recorded_by' => Auth::id(),
            ]);

            $this->processed++;
        }
    }

    /**
     * Parse a date cell that may arrive as an Excel serial number or a plain date string.
     */
    protected function parseDate($value): ?Carbon
    {
        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value));
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'index_number' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required',
            'academic_year' => 'required',
            'bank_reference' => 'nullable',
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
