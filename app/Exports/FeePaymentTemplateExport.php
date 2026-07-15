<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FeePaymentTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample rows to guide admins filling in the template.
     */
    public function array(): array
    {
        return [
            ['1234567', 500.00, '2026-07-12', '2025/2026', 'REF123456'],
            ['2345678', 350.00, '2026-07-12', '2025/2026', ''],
        ];
    }

    /**
     * Column headings expected by FeePaymentImport. reference_number is the student's 7-digit
     * bank reference number used to identify them (not their index number) - bank_reference is
     * a separate, optional column for that specific transaction/teller reference, if any.
     */
    public function headings(): array
    {
        return ['reference_number', 'amount', 'date', 'academic_year', 'bank_reference'];
    }
}
