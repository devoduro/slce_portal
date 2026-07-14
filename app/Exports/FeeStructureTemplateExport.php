<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FeeStructureTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample rows to guide admins filling in the template.
     */
    public function array(): array
    {
        return [
            ['EGE', '100', 'tuition', '2025/2026', 3200.00],
            ['EGE', '', 'graduation', '2025/2026', 150.00],
        ];
    }

    /**
     * Column headings expected by FeeStructureImport. Category must be one of:
     * tuition, graduation, resit. Leave level blank to apply to every level of the programme.
     */
    public function headings(): array
    {
        return ['programme_code', 'level', 'category', 'academic_year', 'amount'];
    }
}
