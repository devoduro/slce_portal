<?php

namespace App\Exports;

use App\Models\Lecturer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LecturerExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Collection $lecturers)
    {
    }

    public function collection()
    {
        return $this->lecturers;
    }

    public function headings(): array
    {
        return ['Name', 'Staff ID', 'Email', 'Phone', 'Department', 'Courses', 'Classes (Current Semester)', 'Workload (Credit Hours)'];
    }

    public function map($lecturer): array
    {
        /** @var Lecturer $lecturer */
        return [
            $lecturer->name,
            $lecturer->staff_id ?? 'N/A',
            $lecturer->email ?? 'N/A',
            $lecturer->phone ?? 'N/A',
            $lecturer->department->name ?? 'N/A',
            $lecturer->courses_count,
            $lecturer->workload_classes,
            $lecturer->workload_credit,
        ];
    }
}
