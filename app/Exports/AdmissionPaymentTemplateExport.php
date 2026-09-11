<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AdmissionPaymentTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['2405974', 3000, 'Mobile Money', 'MTN MoMo', 'TXN123456', ''],
            ['2405974', 300, 'Bank Deposit', 'GCB Bank', 'REF789012', 'RCT-001'],
        ];
    }

    public function headings(): array
    {
        return ['applicant_number', 'amount', 'payment_method', 'bank', 'reference_number', 'receipt_number'];
    }
}
