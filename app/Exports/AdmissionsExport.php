<?php

namespace App\Exports;

use App\Models\Admission;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdmissionsExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    public function __construct(protected Request $request)
    {
    }

    /**
     * Mirrors AdmissionController::index()'s filters exactly, so an export always matches
     * what's currently on screen.
     */
    public function query()
    {
        $query = Admission::query()->with(['programme', 'academicYear']);

        if ($this->request->filled('search')) {
            $search = $this->request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('applicant_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // 'status' on the main admissions list, 'admission_status' on the halls page
        // (which reserves 'status' for its own "unassigned" hall toggle).
        if ($this->request->filled('status')) {
            $query->where('admission_status', $this->request->status);
        } elseif ($this->request->filled('admission_status')) {
            $query->where('admission_status', $this->request->admission_status);
        }

        if ($this->request->filled('payment_status')) {
            $query->where('payment_status', $this->request->payment_status);
        }

        if ($this->request->filled('programme_id')) {
            $query->where('programme_id', $this->request->programme_id);
        }

        if ($this->request->filled('academic_year_id')) {
            $query->where('academic_year_id', $this->request->academic_year_id);
        }

        if ($this->request->filled('hall')) {
            $query->where('hall', $this->request->hall);
        }

        return $query->latest();
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function headings(): array
    {
        return [
            'Applicant Number', 'Reference Number', 'Name', 'Programme', 'Academic Year', 'Level',
            'Gender', 'Phone', 'Email', 'Hall', 'Admission Status', 'Payment Status',
            'Total Billed', 'Total Paid', 'Outstanding',
        ];
    }

    public function map($admission): array
    {
        return [
            $admission->applicant_number,
            $admission->reference_number,
            $admission->full_name,
            $admission->programme->name ?? '',
            $admission->academicYear->name ?? '',
            $admission->level,
            $admission->gender,
            $admission->phone,
            $admission->email,
            $admission->hall,
            ucfirst($admission->admission_status),
            ucfirst(str_replace('_', ' ', $admission->payment_status)),
            number_format($admission->totalBilled(), 2),
            number_format($admission->totalPaid(), 2),
            number_format($admission->outstandingBalance(), 2),
        ];
    }
}
