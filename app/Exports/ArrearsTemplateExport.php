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
            ['1234567', '2022/2023', 350.00, 'Outstanding hostel fee'],
            ['2345678', '2021/2022', 500.00, ''],
        ];
    }

    /**
     * Column headings expected by ArrearsImport. reference_number is the student's 7-digit
     * bank reference number (see Student Reference Numbers), not their index number - it's
     * what the bank uses to identify the student, so it's what bank-sourced debtor lists use.
     */
    public function headings(): array
    {
        return ['reference_number', 'academic_year', 'amount', 'notes'];
    }
}
