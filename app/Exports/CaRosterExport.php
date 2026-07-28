<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CaRosterExport implements FromCollection, WithHeadings, WithMapping
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
        return ['Index Number', 'Student Name', 'Attendance', 'Project', 'Assignment', 'Mid-Semester', 'Total'];
    }

    public function map($row): array
    {
        return [
            $row['student']->index_number,
            $row['student']->full_name,
            $row['attendance_score'],
            $row['ca']->project_score ?? '',
            $row['ca']->assignment_score ?? '',
            $row['ca']->mid_semester_score ?? '',
            $row['total'],
        ];
    }
}
