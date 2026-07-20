<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PartnerSchoolTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample rows to guide admins filling in the template.
     */
    public function array(): array
    {
        return [
            ['Osu Presby Primary', 'early_grade', 'sts', 10, 'Osu, Accra'],
            ['Achimota Basic School', 'jhs', 'internship', 15, 'Achimota, Accra'],
        ];
    }

    /**
     * Column headings expected by PartnerSchoolImport.
     */
    public function headings(): array
    {
        return ['name', 'category', 'type', 'capacity', 'location'];
    }
}
