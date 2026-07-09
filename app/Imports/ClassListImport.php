<?php

namespace App\Imports;

use App\Models\ClassGroup;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class ClassListImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
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
            $className = trim((string) ($row['class_name'] ?? ''));
            $level = trim((string) ($row['level'] ?? ''));

            if ($indexNumber === '' || $className === '') {
                $this->skipped++;
                continue;
            }

            $student = Student::where('index_number', $indexNumber)->first();

            if (!$student) {
                $this->errors[] = "Student with index number {$indexNumber} not found.";
                $this->skipped++;
                continue;
            }

            // An explicit level column is authoritative for this row: it lets a class-list
            // upload also backfill/correct a student's level, rather than requiring it to
            // already be set correctly before the class can be resolved.
            $targetLevel = $level !== '' ? (int) $level : $student->level;

            if ($targetLevel === null) {
                $this->errors[] = "Student {$indexNumber} has no level on file and none was provided in the \"level\" column.";
                $this->skipped++;
                continue;
            }

            $classGroup = ClassGroup::where('name', $className)
                ->where('programme_id', $student->programme_id)
                ->where('level', $targetLevel)
                ->first();

            if (!$classGroup) {
                $this->errors[] = "No class \"{$className}\" found for student {$indexNumber}'s programme at level {$targetLevel}.";
                $this->skipped++;
                continue;
            }

            $student->update(['class_group_id' => $classGroup->id, 'level' => $targetLevel]);

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
            'class_name' => 'required',
            'level' => 'nullable|integer|min:100',
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
