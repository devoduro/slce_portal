<?php

namespace App\Exports;

use App\Models\Course;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CaCoursesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Collection $courses)
    {
    }

    public function collection()
    {
        return $this->courses;
    }

    public function headings(): array
    {
        return ['Code', 'Title', 'Level', 'Programme', 'Semester', 'Lecturer'];
    }

    public function map($course): array
    {
        /** @var Course $course */
        return [
            $course->code,
            $course->title,
            $course->level ?? 'N/A',
            $course->programmes->pluck('code')->join(', ') ?: 'None',
            $course->semester->name ?? 'N/A',
            $course->lecturers->pluck('name')->join(', ') ?: 'Not assigned',
        ];
    }
}
