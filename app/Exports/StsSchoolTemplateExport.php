<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StsSchoolTemplateExport implements FromArray, WithHeadings
{
    public function __construct(protected array $sampleNames = [])
    {
    }

    /**
     * Sample rows to guide admins filling in the template. Uses real partner school names
     * from the database when available, since names must match a school exactly.
     */
    public function array(): array
    {
        $name1 = $this->sampleNames[0] ?? 'Osu Presby Primary';
        $name2 = $this->sampleNames[1] ?? 'Achimota Basic School';

        return [
            ['0524123456', $name1],
            ['0524123457', $name2],
        ];
    }

    /**
     * Column headings expected by StsSchoolImport.
     */
    public function headings(): array
    {
        return ['index_number', 'school'];
    }
}
