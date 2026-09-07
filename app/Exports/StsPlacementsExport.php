<?php

namespace App\Exports;

use App\Models\StsPlacement;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Excel export of the STS/Internship placement list. Takes the already-filtered query from
 * StsPlacementController so the spreadsheet always matches exactly what the page is showing -
 * same term, same category/type/search filters - rather than re-deriving them and drifting.
 */
class StsPlacementsExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    protected Builder $placements;

    public function __construct(Builder $placements)
    {
        $this->placements = $placements;
    }

    public function query()
    {
        return $this->placements->with(['student.programme', 'partnerSchool', 'lecturer', 'secondLecturer']);
    }

    /**
     * Chunked so a full term (1000+ placements, each touching several relations) streams out
     * instead of being held in memory all at once.
     */
    public function chunkSize(): int
    {
        return 200;
    }

    public function headings(): array
    {
        return [
            'Index Number',
            'Full Name',
            'Programme',
            'Level',
            'Type',
            'Partner School',
            'School Location',
            'Primary Supervisor',
            'Second Supervisor',
            'Status',
            'School Selected On',
            'Supervisor Assigned On',
            'Letter Printed On',
        ];
    }

    public function map($placement): array
    {
        return [
            $placement->student->index_number ?? '',
            $placement->student->full_name ?? '',
            $placement->student->programme->name ?? 'N/A',
            $placement->level,
            $placement->type === StsPlacement::TYPE_INTERNSHIP ? 'Internship' : 'STS',
            $placement->partnerSchool->name ?? 'Not selected',
            $placement->partnerSchool->location ?? '',
            $placement->lecturer->name ?? 'Not assigned',
            $placement->secondLecturer->name ?? '',
            $placement->statusLabel(),
            $placement->selected_at?->format('Y-m-d H:i'),
            $placement->supervisor_assigned_at?->format('Y-m-d H:i'),
            $placement->letter_printed_at?->format('Y-m-d H:i'),
        ];
    }
}
