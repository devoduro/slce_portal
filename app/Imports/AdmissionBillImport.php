<?php

namespace App\Imports;

use App\Models\Admission;
use App\Models\FeeCategory;
use App\Models\User;
use App\Services\AdmissionService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Bulk-bill many admissions at once from a spreadsheet - mirrors
 * StudentFeeChargeImport's shape, keyed on applicant_number instead of
 * index_number since these applicants have no index number yet.
 *
 * Purely additive, same as the single "Add Item" form on the billing screen: each
 * row always creates a new AdmissionBillItem via AdmissionService::addBillItem() (so
 * the audit trail and payment_status recalculation stay consistent with manual entry).
 * Re-uploading the same file bills the same amount again rather than updating it - if
 * a row was wrong, remove the mistaken item on the billing screen rather than
 * re-uploading a "corrected" file.
 */
class AdmissionBillImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    protected int $processed = 0;
    protected int $skipped = 0;
    protected array $errors = [];

    public function __construct(protected User $officer)
    {
    }

    /**
     * @param Failure[] $failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = 'Row ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            $this->skipped++;
        }
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $applicantNumber = trim((string) ($row['applicant_number'] ?? ''));
            $category = trim((string) ($row['category'] ?? ''));
            $amount = $row['amount'] ?? null;

            if ($applicantNumber === '' || $category === '' || $amount === null || $amount === '') {
                $this->skipped++;
                continue;
            }

            $admission = Admission::where('applicant_number', $applicantNumber)->first();

            if (!$admission) {
                $this->errors[] = "Row {$rowNumber}: applicant number {$applicantNumber} not found.";
                $this->skipped++;
                continue;
            }

            if (!array_key_exists($category, FeeCategory::options())) {
                $this->errors[] = "Row {$rowNumber}: unknown category \"{$category}\" for {$applicantNumber} (expected one of: " . implode(', ', array_keys(FeeCategory::options())) . ').';
                $this->skipped++;
                continue;
            }

            if (!is_numeric($amount) || (float) $amount <= 0) {
                $this->errors[] = "Row {$rowNumber}: invalid amount for {$applicantNumber}.";
                $this->skipped++;
                continue;
            }

            AdmissionService::addBillItem($admission, [
                'category' => $category,
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'amount' => (float) $amount,
                'payment_deadline' => $this->parseDate($row['payment_deadline'] ?? null),
                'notes' => 'Bulk uploaded',
            ], $this->officer);

            $this->processed++;
        }
    }

    protected function parseDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'applicant_number' => 'required',
            'category' => 'required',
            'amount' => 'required|numeric|gt:0',
        ];
    }

    public function getStats(): array
    {
        return [
            'processed' => $this->processed,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
