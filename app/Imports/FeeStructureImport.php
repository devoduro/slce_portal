<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Programme;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class FeeStructureImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
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
            $programmeCode = trim((string) ($row['programme_code'] ?? ''));
            $academicYearName = trim((string) ($row['academic_year'] ?? ''));
            $category = trim((string) ($row['category'] ?? ''));
            $level = trim((string) ($row['level'] ?? ''));
            $amount = $row['amount'] ?? null;

            if ($programmeCode === '' || $academicYearName === '' || $category === '' || $amount === null || $amount === '') {
                $this->skipped++;
                continue;
            }

            $programme = Programme::where('code', $programmeCode)->first();

            if (!$programme) {
                $this->errors[] = "Programme with code {$programmeCode} not found.";
                $this->skipped++;
                continue;
            }

            $academicYear = AcademicYear::where('name', $academicYearName)->first();

            if (!$academicYear) {
                $this->errors[] = "Academic year \"{$academicYearName}\" not found for programme {$programmeCode}.";
                $this->skipped++;
                continue;
            }

            if (!array_key_exists($category, FeeCategory::options())) {
                $this->errors[] = "Unknown category \"{$category}\" for programme {$programmeCode} (expected one of: " . implode(', ', array_keys(FeeCategory::options())) . ').';
                $this->skipped++;
                continue;
            }

            if (!is_numeric($amount) || (float) $amount <= 0) {
                $this->errors[] = "Invalid amount for programme {$programmeCode}.";
                $this->skipped++;
                continue;
            }

            FeeStructure::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'programme_id' => $programme->id,
                    'level' => $level === '' ? null : (int) $level,
                    'category' => $category,
                ],
                ['amount' => (float) $amount]
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
            'programme_code' => 'required',
            'academic_year' => 'required',
            'category' => 'required',
            'amount' => 'required|numeric|min:0.01',
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
