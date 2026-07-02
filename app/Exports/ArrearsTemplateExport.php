<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ArrearsTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample rows to guide admins filling in the template.
     */
    public function array(): array
    {
        return [
            ['2021SLE0012', '2022/2023', 350.00, 'Outstanding hostel fee'],
            ['2020SLE0045', '2021/2022', 500.00, ''],
        ];
    }

    /**
     * Column headings expected by ArrearsImport.
     */
    public function headings(): array
    {
        return ['index_number', 'academic_year', 'amount', 'notes'];
    }
}
