<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ResitResultTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample row to guide staff filling in the template.
     */
    public function array(): array
    {
        return [
            [2100131, '2022/2023', 'Semester 2', 'EBC244', 'INTRODUCTION TO LITERATURE IN ENGLISH', 'A', 'Y/N'],
        ];
    }

    /**
     * Column headings expected by ResitResultImport.
     */
    public function headings(): array
    {
        return ['index number', 'academic year', 'semester', 'course code', 'Course Title', 'grade', 'is resit'];
    }
}
