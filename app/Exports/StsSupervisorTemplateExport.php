<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StsSupervisorTemplateExport implements FromArray, WithHeadings
{
    public function __construct(protected array $sampleNames = [])
    {
    }

    /**
     * Sample rows to guide admins filling in the template. Uses real lecturer names from the
     * database when available, since names must match a lecturer exactly.
     */
    public function array(): array
    {
        $name1 = $this->sampleNames[0] ?? 'Jane Doe';
        $name2 = $this->sampleNames[1] ?? 'John Smith';

        return [
            ['0524123456', $name1, $name2],
            ['0524123457', $name1, ''],
        ];
    }

    /**
     * Column headings expected by StsSupervisorImport. supervisor_2 may be left blank -
     * a placement can validly have only one supervisor.
     */
    public function headings(): array
    {
        return ['index_number', 'supervisor_1', 'supervisor_2'];
    }
}
