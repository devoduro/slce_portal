<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CourseStudentsExport implements FromCollection, WithHeadings, WithMapping
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
        return ['Index Number', 'Student Name', 'Score', 'Grade'];
    }

    public function map($row): array
    {
        return [
            $row['student']->index_number,
            $row['student']->full_name,
            $row['result']->score ?? '',
            $row['result']->grade ?? 'Not graded',
        ];
    }
}
