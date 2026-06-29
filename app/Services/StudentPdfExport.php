<?php

namespace App\Services;

use PDF;
use App\Models\Student;

class StudentPdfExport
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function export()
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
        
        $students = $query->get();
        
        $pdf = PDF::loadView('exports.students-pdf', [
            'students' => $students
        ]);
        
        return $pdf->download('students.pdf');
    }
}
