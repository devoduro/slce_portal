<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentFeeChargeTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample rows to guide admins filling in the template.
     */
    public function array(): array
    {
        return [
            ['2021SLE0012', 'resit', 50.00, '2025/2026', 'Resit - MTH 101'],
            ['2020SLE0045', 'graduation', 150.00, '2025/2026', ''],
        ];
    }

    /**
     * Column headings expected by StudentFeeChargeImport. Category must be one of:
     * tuition, graduation, resit.
     */
    public function headings(): array
    {
        return ['index_number', 'category', 'amount', 'academic_year', 'notes'];
    }
}
