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
            ['2021SLE0012', 500.00, '2026-07-12', '2025/2026', 'REF123456'],
            ['2020SLE0045', 350.00, '2026-07-12', '2025/2026', ''],
        ];
    }

    /**
     * Column headings expected by FeePaymentImport.
     */
    public function headings(): array
    {
        return ['index_number', 'amount', 'date', 'academic_year', 'bank_reference'];
    }
}
