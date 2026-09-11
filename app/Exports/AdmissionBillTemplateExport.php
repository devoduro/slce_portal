<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AdmissionBillTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['2405974', 'tuition', 'School Fees', 3000, '2026-10-27'],
            ['2405974', 'tuition', 'Admission Fee', 300, '2026-10-27'],
        ];
    }

    public function headings(): array
    {
        return ['applicant_number', 'category', 'description', 'amount', 'payment_deadline'];
    }
}
