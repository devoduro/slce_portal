<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GraduatesExport implements FromCollection, WithHeadings, WithMapping
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
        return ['Index Number', 'Full Name', 'Programme', 'Graduated', 'Level At Graduation', 'Phone', 'Balance', 'Status'];
    }

    public function map($row): array
    {
        $student = $row['student'];

        return [
            $student->index_number,
            $student->full_name,
            $student->programme->name ?? 'N/A',
            $student->graduatedAcademicYear->name ?? 'N/A',
            $student->level ?? 'N/A',
            $student->phone,
            number_format($row['balance'], 2),
            ucfirst($row['status']),
        ];
    }
}
