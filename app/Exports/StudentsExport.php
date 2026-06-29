<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Student::with('programme');
        
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
        
        return $query->get();
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
            number_format($student->calculateCGPA(), 2),
            $student->emergency_contact_name,
            $student->emergency_contact_phone,
            $student->address
        ];
    }
}
