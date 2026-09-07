<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class StudentsExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = Student::query()->with(['programme', 'results.course', 'graduatedAcademicYear']);

        // Apply filters if they exist
        if ($this->request->has('search') && $this->request->search) {
            $searchTerm = $this->request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('full_name', 'like', "%{$searchTerm}%")
                  ->orWhere('index_number', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }

        if ($this->request->has('programme_id') && $this->request->programme_id) {
            $query->where('programme_id', $this->request->programme_id);
        }

        if ($this->request->has('gender') && $this->request->gender) {
            $query->where('gender', $this->request->gender);
        }

        if ($this->request->filled('graduated_academic_year_id')) {
            $query->where('status', 'graduated')->where('graduated_academic_year_id', $this->request->graduated_academic_year_id);
        } elseif ($this->request->has('level') && $this->request->level) {
            if ($this->request->level === 'graduated') {
                $query->where('status', 'graduated');
            } else {
                $query->where('level', $this->request->level)->where('status', '!=', 'graduated');
            }
        }

        return $query;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function headings(): array
    {
        return [
            'Index Number',
            'Full Name',
            'Email',
            'Phone',
            'Gender',
            'Date of Birth',
            'Programme',
            'Level',
            'CGPA',
            'Emergency Contact',
            'Emergency Phone',
            'Address'
        ];
    }

    public function map($student): array
    {
        return [
            $student->index_number,
            $student->full_name,
            $student->email,
            $student->phone,
            $student->gender,
            $student->date_of_birth,
            $student->programme->name ?? 'N/A',
            $student->levelLabel(),
            number_format($student->calculateCGPA(), 2),
            $student->emergency_contact_name,
            $student->emergency_contact_phone,
            $student->address
        ];
    }
}
