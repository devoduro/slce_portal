<?php

namespace App\Imports;

use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StsPlacement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Bulk-assigns primary/second supervisors to STS or Internship placements for the current
 * term, matched by student index number and lecturer name. Supervisor 2 is optional - a
 * placement can validly end up with only one supervisor.
 */
class StsSupervisorImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
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
            $supervisor1Name = trim((string) ($row['supervisor_1'] ?? ''));
            $supervisor2Name = trim((string) ($row['supervisor_2'] ?? ''));

            if ($indexNumber === '') {
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

            $lecturer1 = null;
            if ($supervisor1Name !== '') {
                $lecturer1 = $this->findLecturer($supervisor1Name);

                if (!$lecturer1) {
                    $this->errors[] = "Row for {$indexNumber}: supervisor 1 \"{$supervisor1Name}\" does not match exactly one lecturer.";
                    $this->skipped++;
                    continue;
                }
            }

            $lecturer2 = null;
            if ($supervisor2Name !== '') {
                $lecturer2 = $this->findLecturer($supervisor2Name);

                if (!$lecturer2) {
                    $this->errors[] = "Row for {$indexNumber}: supervisor 2 \"{$supervisor2Name}\" does not match exactly one lecturer.";
                    $this->skipped++;
                    continue;
                }
            }

            if ($lecturer1 && $lecturer2 && $lecturer1->id === $lecturer2->id) {
                $this->errors[] = "Row for {$indexNumber}: supervisor 1 and supervisor 2 cannot be the same lecturer.";
                $this->skipped++;
                continue;
            }

            $placement->update([
                'lecturer_id' => $lecturer1?->id,
                'second_lecturer_id' => $lecturer2?->id,
                'supervisor_assigned_at' => now(),
            ]);

            $this->processed++;
        }
    }

    /**
     * Case-insensitive exact match on lecturer name. Returns null if not found or ambiguous
     * (more than one lecturer sharing that exact name) rather than guessing.
     */
    protected function findLecturer(string $name): ?Lecturer
    {
        $matches = Lecturer::whereRaw('LOWER(name) = ?', [strtolower($name)])->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function rules(): array
    {
        return [
            'index_number' => 'required',
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
