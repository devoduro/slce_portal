<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AdmissionTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample rows matching the institution's real admission list export, to guide staff
     * filling in the template. See AdmissionImport for how each column is read.
     */
    public function array(): array
    {
        return [
            [2405974, 'MISS', 'ABABIO', 'COIETAH NYARKO', 'FEMALE', '2003/05/12', '0244000111', 'ababio@example.com', 100, 'B.ED UPPER PRIMARY EDUCATION', 2026, 2030],
            [2428296, 'MISS', 'ABANGA', 'MARY', 'FEMALE', '2001/08/20', '0248895394', 'mary@example.com', 100, 'B.ED UPPER PRIMARY EDUCATION', '', ''],
        ];
    }

    public function headings(): array
    {
        return ['Applicant Number', 'Title', 'surname', 'othernames', 'sex', 'dob (YYYY/MM/DD)', 'mobile', 'email', 'level', 'program', 'dateofadmission', 'dateofcompletion'];
    }
}
