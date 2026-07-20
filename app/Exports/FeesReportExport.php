<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FeesReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Collection $rows)
    {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Programme', 'Level', 'Students', 'Fee Amount', 'Expected', 'Collected', 'Balance', '% Collected'];
    }

    public function map($row): array
    {
        return [
            $row['programme'],
            $row['level'],
            $row['students'],
            $row['fee_amount'] !== null ? number_format($row['fee_amount'], 2) : 'Not set',
            number_format($row['expected'], 2),
            number_format($row['collected'], 2),
            number_format($row['balance'], 2),
            $row['percentage'] . '%',
        ];
    }
}
