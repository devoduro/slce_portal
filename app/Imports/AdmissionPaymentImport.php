<?php

namespace App\Imports;

use App\Models\Admission;
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
 * Bulk-record admission payments from a spreadsheet (e.g. a bank/MoMo statement) -
 * mirrors FeePaymentImport's shape, keyed on applicant_number.
 *
 * Every row lands as AdmissionPayment::STATUS_RECORDED, exactly like a single payment
 * entered by hand on the billing screen - it deliberately does NOT auto-verify or
 * auto-confirm. Skipping that step here would let a bulk upload silently bypass the
 * payment gate's core rule that only Accounts, having actually checked the evidence,
 * confirms a payment. Each row still needs a human to Verify then Confirm afterward.
 */
class AdmissionPaymentImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
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
            $amount = $row['amount'] ?? null;

            if ($applicantNumber === '' || $amount === null || $amount === '') {
                $this->skipped++;
                continue;
            }

            $admission = Admission::where('applicant_number', $applicantNumber)->first();

            if (!$admission) {
                $this->errors[] = "Row {$rowNumber}: applicant number {$applicantNumber} not found.";
                $this->skipped++;
                continue;
            }

            if (!is_numeric($amount) || (float) $amount <= 0) {
                $this->errors[] = "Row {$rowNumber}: invalid amount for {$applicantNumber}.";
                $this->skipped++;
                continue;
            }

            AdmissionService::recordPayment($admission, [
                'amount' => (float) $amount,
                'payment_method' => trim((string) ($row['payment_method'] ?? '')) ?: 'Other',
                'bank' => trim((string) ($row['bank'] ?? '')) ?: null,
                'reference_number' => trim((string) ($row['reference_number'] ?? '')) ?: null,
                'receipt_number' => trim((string) ($row['receipt_number'] ?? '')) ?: null,
                'remarks' => 'Bulk uploaded',
            ], $this->officer);

            $this->processed++;
        }
    }

    public function rules(): array
    {
        return [
            'applicant_number' => 'required',
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
