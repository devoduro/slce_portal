<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentFeesExport implements FromCollection, WithHeadings, WithMapping
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
        return ['Index Number', 'Full Name', 'Programme', 'Level', 'Arrears', 'Fee Amount', 'Paid', 'Balance', 'Status', '% Paid'];
    }

    public function map($row): array
    {
        $student = $row['student'];

        return [
            $student->index_number,
            $student->full_name,
            $student->programme->name ?? 'N/A',
            $student->level,
            number_format($row['arrears'], 2),
            $row['fee_amount'] !== null ? number_format($row['fee_amount'], 2) : 'Not set',
            number_format($row['paid'], 2),
            number_format($row['balance'], 2),
            ucfirst($row['status']),
            $row['percentage'] . '%',
        ];
    }
}
