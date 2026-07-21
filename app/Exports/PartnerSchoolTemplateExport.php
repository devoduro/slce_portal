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
            ['Osu Presby Primary', 'early_grade', 'sts', 10, 10, 10, 30, 'Osu, Accra'],
            ['Achimota Basic School', 'jhs_le', 'sts', 8, 6, 6, '', 'Achimota, Accra'],
        ];
    }

    /**
     * Column headings expected by PartnerSchoolImport. total_capacity may be left blank -
     * it defaults to the sum of the three level capacities (as in the second example row).
     * This upload is for STS schools (levels 100-300) - Internship (level 400) capacity is
     * set separately when editing a school.
     */
    public function headings(): array
    {
        return ['name', 'category', 'type', 'level_100_capacity', 'level_200_capacity', 'level_300_capacity', 'total_capacity', 'location'];
    }
}
