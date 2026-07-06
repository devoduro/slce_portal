<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClassListTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample rows to guide admins filling in the template.
     */
    public function array(): array
    {
        return [
            ['2021SLE0012', '100A'],
            ['2021SLE0045', '100B'],
        ];
    }

    /**
     * Column headings expected by ClassListImport.
     */
    public function headings(): array
    {
        return ['index_number', 'class_name'];
    }
}
