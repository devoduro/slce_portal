<?php

namespace App\Imports;

use App\Models\PartnerSchool;
use App\Models\Student;
use App\Models\StsPlacement;
use App\Services\StsPlacementService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Bulk-assigns (or changes) a partner school on STS or Internship placements for the current
 * term, matched by student index number and school name. Delegates the actual assignment to
 * StsPlacementService::adminAssignSchool() so category/type match and quota are enforced
 * identically to the single-row "Change School" action on the placements page.
 */
class StsSchoolImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    protected int $processed = 0;
    protected int $skipped = 0;
    protected array $errors = [];

    public function __construct(protected int $stsTermId, protected string $type)
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

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $indexNumber = trim((string) ($row['index_number'] ?? ''));
            $schoolName = trim((string) ($row['school'] ?? ''));

            if ($indexNumber === '' || $schoolName === '') {
                $this->skipped++;
                continue;
            }

            $student = Student::where('index_number', $indexNumber)->first();

            if (!$student) {
                $this->errors[] = "No student found with index number \"{$indexNumber}\".";
                $this->skipped++;
                continue;
            }

            $placement = StsPlacement::where('student_id', $student->id)
                ->where('sts_term_id', $this->stsTermId)
                ->where('type', $this->type)
                ->first();

            if (!$placement) {
                $this->errors[] = "{$student->full_name} ({$indexNumber}) has no " . ucfirst($this->type) . ' placement in the current term.';
                $this->skipped++;
                continue;
            }

            $school = $this->findSchool($schoolName);

            if (!$school) {
                $this->errors[] = "Row for {$indexNumber}: school \"{$schoolName}\" does not match exactly one partner school.";
                $this->skipped++;
                continue;
            }

            try {
                StsPlacementService::adminAssignSchool($placement, $school);
                $this->processed++;
            } catch (ValidationException $e) {
                $this->errors[] = "Row for {$indexNumber}: " . collect($e->errors())->flatten()->first();
                $this->skipped++;
            }
        }
    }

    /**
     * Case-insensitive exact match on school name. Returns null if not found or ambiguous
     * (more than one school sharing that exact name) rather than guessing.
     */
    protected function findSchool(string $name): ?PartnerSchool
    {
        $matches = PartnerSchool::whereRaw('LOWER(name) = ?', [strtolower($name)])->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function rules(): array
    {
        return [
            'index_number' => 'required',
            'school' => 'required',
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
