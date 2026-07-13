<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HallTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample rows to guide admins filling in the template.
     */
    public function array(): array
    {
        return [
            ['2021SLE0012', 'Mary Hall'],
            ['2020SLE0045', 'Joseph Hall'],
        ];
    }

    /**
     * Column headings expected by HallImport.
     */
    public function headings(): array
    {
        return ['index_number', 'hall'];
    }
}
