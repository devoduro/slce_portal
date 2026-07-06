<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Lecturer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class LecturerImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
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
            $name = trim((string) ($row['name'] ?? ''));
            $email = trim((string) ($row['email'] ?? '')) ?: null;
            $phone = trim((string) ($row['phone'] ?? '')) ?: null;
            $staffId = trim((string) ($row['staff_id'] ?? '')) ?: null;
            $departmentName = trim((string) ($row['department'] ?? ''));

            if ($name === '') {
                $this->skipped++;
                continue;
            }

            $departmentId = null;
            if ($departmentName !== '') {
                $department = Department::where('name', $departmentName)->first();

                if (!$department) {
                    $this->errors[] = "Department \"{$departmentName}\" not found for lecturer {$name}.";
                    $this->skipped++;
                    continue;
                }

                $departmentId = $department->id;
            }

            // Match an existing lecturer by staff ID first, then by email, so re-uploading updates rather than duplicates.
            $existingByStaffId = $staffId ? Lecturer::where('staff_id', $staffId)->first() : null;
            $existingByEmail = $email ? Lecturer::where('email', $email)->first() : null;

            if ($existingByStaffId && $existingByEmail && $existingByStaffId->id !== $existingByEmail->id) {
                $this->errors[] = "Staff ID \"{$staffId}\" and email \"{$email}\" belong to different existing lecturers for {$name}.";
                $this->skipped++;
                continue;
            }

            $existing = $existingByStaffId ?? $existingByEmail;

            if ($email && Lecturer::where('email', $email)->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
                $this->errors[] = "Email \"{$email}\" is already used by another lecturer ({$name}).";
                $this->skipped++;
                continue;
            }

            if ($staffId && Lecturer::where('staff_id', $staffId)->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
                $this->errors[] = "Staff ID \"{$staffId}\" is already used by another lecturer ({$name}).";
                $this->skipped++;
                continue;
            }

            $attributes = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'staff_id' => $staffId,
                'department_id' => $departmentId,
            ];

            if ($existing) {
                $existing->update($attributes);
            } else {
                Lecturer::create($attributes);
            }

            $this->processed++;
        }
    }

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
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
