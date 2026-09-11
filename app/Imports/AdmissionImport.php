<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Admission;
use App\Models\Programme;
use App\Models\Student;
use App\Models\User;
use App\Services\AdmissionService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Bulk-imports admission records from the institution's own admission list export.
 * Expected columns (heading row, normalized to snake_case by Maatwebsite):
 * applicant_number, title, surname, othernames, sex, dob_yyyymmdd, mobile, email,
 * level, program, dateofadmission. dateofcompletion is read but not stored.
 *
 * applicant_number doubles as both the applicant's login id and (copied into
 * reference_number) the institution's own reference number - real admission lists
 * arrive with this 7-digit number already assigned, unlike the single-applicant "Import
 * Applicant" form, which generates an internal APPyyyynnnn number and leaves
 * reference_number for staff to fill in later.
 */
class AdmissionImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    protected int $processed = 0;
    protected int $skipped = 0;
    protected int $emailed = 0;
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
            $rowNumber = $index + 2; // +1 for zero-index, +1 for the heading row itself.

            $applicantNumber = trim((string) ($row['applicant_number'] ?? ''));

            if (!preg_match('/^\d{7}$/', $applicantNumber)) {
                $this->errors[] = "Row {$rowNumber}: applicant number must be exactly 7 digits (got \"{$applicantNumber}\").";
                $this->skipped++;
                continue;
            }

            if (Admission::where('applicant_number', $applicantNumber)->exists()) {
                $this->errors[] = "Row {$rowNumber}: applicant number {$applicantNumber} has already been imported.";
                $this->skipped++;
                continue;
            }

            if (Student::where('reference_number', $applicantNumber)->orWhere('index_number', $applicantNumber)->exists()) {
                $this->errors[] = "Row {$rowNumber}: {$applicantNumber} already belongs to an existing student.";
                $this->skipped++;
                continue;
            }

            $fullName = trim(trim((string) ($row['surname'] ?? '')) . ' ' . trim((string) ($row['othernames'] ?? '')));

            if ($fullName === '') {
                $this->errors[] = "Row {$rowNumber}: surname/othernames are required.";
                $this->skipped++;
                continue;
            }

            $gender = $this->normalizeGender($row['sex'] ?? null);

            if (!$gender) {
                $this->errors[] = "Row {$rowNumber}: unrecognized sex value \"{$row['sex']}\" (expected Male/Female/Other).";
                $this->skipped++;
                continue;
            }

            $dateOfBirth = $this->normalizeDate($row['dob_yyyymmdd'] ?? null);

            $programme = $this->findProgramme($row['program'] ?? null);

            if (!$programme) {
                $this->errors[] = "Row {$rowNumber}: programme \"{$row['program']}\" not found.";
                $this->skipped++;
                continue;
            }

            $academicYear = $this->findAcademicYear($row['dateofadmission'] ?? null);

            if (!$academicYear) {
                $this->errors[] = "Row {$rowNumber}: could not determine an academic year (dateofadmission was \"{$row['dateofadmission']}\" and there is no current academic year set).";
                $this->skipped++;
                continue;
            }

            $email = $this->normalizeEmail($row['email'] ?? null);
            $phone = $this->normalizePhone($row['mobile'] ?? null);

            try {
                $admission = AdmissionService::import([
                    'full_name' => $fullName,
                    'title' => trim((string) ($row['title'] ?? '')) ?: null,
                    'email' => $email,
                    'phone' => $phone,
                    'programme_id' => $programme->id,
                    'academic_year_id' => $academicYear->id,
                    'level' => (int) ($row['level'] ?? 100) ?: 100,
                    'gender' => $gender,
                    'date_of_birth' => $dateOfBirth,
                ], $this->officer, $applicantNumber, $applicantNumber);

                $this->processed++;
                if ($admission->credentials_emailed) {
                    $this->emailed++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNumber}: {$e->getMessage()}";
                $this->skipped++;
            }
        }
    }

    public function rules(): array
    {
        return [
            'applicant_number' => 'required',
            'surname' => 'required',
        ];
    }

    protected function normalizeGender(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'male', 'm' => 'Male',
            'female', 'f' => 'Female',
            'other' => 'Other',
            default => null,
        };
    }

    /**
     * Excel stores dates as a serial day count regardless of how the column is
     * labelled/formatted - convert that, but fall back to parsing a literal date string
     * for files where the cell really does contain text.
     */
    protected function normalizeDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Ghanaian mobile numbers are 10 digits starting with 0; Excel frequently strips the
     * leading zero when a phone column is stored/typed as a number rather than text.
     */
    protected function normalizePhone(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '0') {
            return null;
        }

        if (preg_match('/^\d{9}$/', $value)) {
            return '0' . $value;
        }

        return $value;
    }

    protected function normalizeEmail(mixed $value): ?string
    {
        $value = trim((string) $value);

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    protected function findProgramme(mixed $name): ?Programme
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return Programme::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
    }

    /**
     * dateofadmission in the source file is a bare year (e.g. 2026), not a full date -
     * match it against the academic year whose name starts with that year. Falls back to
     * the current academic year when the column is blank or unmatched.
     */
    protected function findAcademicYear(mixed $year): ?AcademicYear
    {
        $year = trim((string) $year);

        if ($year !== '' && preg_match('/^\d{4}$/', $year)) {
            $match = AcademicYear::where('name', 'like', $year . '/%')->first();

            if ($match) {
                return $match;
            }
        }

        return AcademicYear::where('is_current', true)->first();
    }

    public function getStats(): array
    {
        return [
            'processed' => $this->processed,
            'skipped' => $this->skipped,
            'emailed' => $this->emailed,
            'errors' => $this->errors,
        ];
    }
}
