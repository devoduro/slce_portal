<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReferenceNumberTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample rows to guide admins filling in the template.
     */
    public function array(): array
    {
        return [
            ['2021SLE0012', '1234567'],
            ['2020SLE0045', '2345678'],
        ];
    }

    /**
     * Column headings expected by ReferenceNumberImport.
     */
    public function headings(): array
    {
        return ['index_number', 'reference_number'];
    }
}
