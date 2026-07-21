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

    protected const CATEGORIES = ['early_grade', 'upper_primary', 'jhs_le', 'jhs_he'];
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
            $level100 = (int) ($row['level_100_capacity'] ?? 0);
            $level200 = (int) ($row['level_200_capacity'] ?? 0);
            $level300 = (int) ($row['level_300_capacity'] ?? 0);
            $totalCapacityRaw = $row['total_capacity'] ?? null;
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

            // Total capacity is informational (e.g. a physical building limit) - if left blank,
            // default to the sum of the three level quotas, which are what actually gate
            // selection via PartnerSchool::availableQuota().
            $totalCapacity = ($totalCapacityRaw !== null && trim((string) $totalCapacityRaw) !== '')
                ? (int) $totalCapacityRaw
                : ($level100 + $level200 + $level300);

            $attributes = [
                'name' => $name,
                'location' => $location,
                'category' => $category,
                'type' => $type,
                'capacity_level_100' => $level100,
                'capacity_level_200' => $level200,
                'capacity_level_300' => $level300,
                'total_capacity' => $totalCapacity,
            ];

            // Matched on name + category + type together, not name alone - two schools can
            // share a name but be genuinely different records (e.g. the same building hosting
            // both a jhs_le and a jhs_he placement, or both an STS and an Internship slot).
            // Re-uploading the exact same name/category/type combination updates that record;
            // a name match with a different category or type creates a new, separate row
            // instead of silently overwriting the existing one's category/type.
            // capacity_level_400 (Internship's level) isn't part of this "for STS schools"
            // upload, so it's left untouched on existing rows and defaults to 0 for new ones.
            $existing = PartnerSchool::where('name', $name)
                ->where('category', $category)
                ->where('type', $type)
                ->first();

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
            'level_100_capacity' => 'required|numeric|min:0',
            'level_200_capacity' => 'required|numeric|min:0',
            'level_300_capacity' => 'required|numeric|min:0',
            'total_capacity' => 'nullable|numeric|min:0',
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
