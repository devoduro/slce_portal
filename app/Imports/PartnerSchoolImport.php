<?php

namespace App\Imports;

use App\Models\PartnerSchool;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class PartnerSchoolImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    protected int $processed = 0;
    protected int $skipped = 0;
    protected array $errors = [];

    protected const CATEGORIES = ['early_grade', 'upper_primary', 'jhs'];
    protected const TYPES = ['sts', 'internship'];

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
            $category = strtolower(trim((string) ($row['category'] ?? '')));
            $type = strtolower(trim((string) ($row['type'] ?? '')));
            $capacity = (int) ($row['capacity'] ?? 0);
            $location = trim((string) ($row['location'] ?? '')) ?: null;

            if ($name === '') {
                $this->skipped++;
                continue;
            }

            if (!in_array($category, self::CATEGORIES, true)) {
                $this->errors[] = "Invalid category \"{$row['category']}\" for school {$name}. Must be one of: " . implode(', ', self::CATEGORIES) . '.';
                $this->skipped++;
                continue;
            }

            if (!in_array($type, self::TYPES, true)) {
                $this->errors[] = "Invalid type \"{$row['type']}\" for school {$name}. Must be one of: " . implode(', ', self::TYPES) . '.';
                $this->skipped++;
                continue;
            }

            $attributes = [
                'name' => $name,
                'location' => $location,
                'category' => $category,
                'type' => $type,
                'capacity_level_100' => $capacity,
                'capacity_level_200' => $capacity,
                'capacity_level_300' => $capacity,
                'capacity_level_400' => $capacity,
            ];

            // Re-uploading with the same name updates rather than duplicates.
            $existing = PartnerSchool::where('name', $name)->first();

            if ($existing) {
                $existing->update($attributes);
            } else {
                PartnerSchool::create($attributes);
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
            'category' => 'required',
            'type' => 'required',
            'capacity' => 'required|numeric',
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
