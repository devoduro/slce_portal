<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports the (already filtered) resit list - students with an outstanding
 * grade E result - shown by ResultController::resitList().
 */
class ResitListExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $results;

    public function __construct(Collection $results)
    {
        $this->results = $results;
    }

    public function collection(): Collection
    {
        return $this->results;
    }

    public function headings(): array
    {
        return [
            'Index Number',
            'Student Name',
            'Programme',
            'Course Code',
            'Course Title',
            'Academic Year',
            'Semester',
            'Grade',
        ];
    }

    /**
     * @param \App\Models\Result $result
     */
    public function map($result): array
    {
        return [
            $result->student->index_number ?? '',
            $result->student->full_name ?? '',
            $result->student->programme->name ?? '',
            $result->course->code ?? '',
            $result->course->title ?? '',
            $result->academicYear->name ?? '',
            $result->semester->name ?? '',
            $result->grade,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9E9E9'],
            ],
        ]);

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setWidth(20);
        }

        return $sheet;
    }
}
