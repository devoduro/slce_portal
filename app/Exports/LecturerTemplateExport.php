<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LecturerTemplateExport implements FromArray, WithHeadings
{
    /**
     * Sample rows to guide admins filling in the template.
     */
    public function array(): array
    {
        return [
            ['John Mensah', 'john.mensah@slce.edu.gh', '0244000000', 'STF001', 'Mathematics'],
            ['Ama Boateng', 'ama.boateng@slce.edu.gh', '0201234567', 'STF002', ''],
        ];
    }

    /**
     * Column headings expected by LecturerImport.
     */
    public function headings(): array
    {
        return ['name', 'email', 'phone', 'staff_id', 'department'];
    }
}
