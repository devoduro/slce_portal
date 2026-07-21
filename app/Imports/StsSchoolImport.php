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

            $school = $this->findSchool($schoolName, $student->programme->sts_category ?? null);

            if (!$school) {
                $this->errors[] = "Row for {$indexNumber}: school \"{$schoolName}\" does not match exactly one partner school for {$student->full_name}'s programme category.";
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
     * Case-insensitive exact match on school name. A name alone can be ambiguous now that the
     * same physical school can have multiple partner_schools rows (e.g. one for jhs_le and one
     * for jhs_he students) - when that happens, disambiguate using the student's own programme
     * category before giving up. Returns null if still not found, or still ambiguous.
     */
    protected function findSchool(string $name, ?string $category): ?PartnerSchool
    {
        $matches = PartnerSchool::whereRaw('LOWER(name) = ?', [strtolower($name)])->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1 && $category) {
            $categoryMatches = $matches->where('category', $category);

            if ($categoryMatches->count() === 1) {
                return $categoryMatches->first();
            }
        }

        return null;
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
