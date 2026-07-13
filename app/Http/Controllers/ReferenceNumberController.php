<?php

namespace App\Http\Controllers;

use App\Exports\ReferenceNumberTemplateExport;
use App\Imports\ReferenceNumberImport;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ReferenceNumberController extends Controller
{
    /**
     * Display a listing of students with their assigned reference numbers.
     */
    public function index(Request $request)
    {
        $query = Student::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('index_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status === 'assigned') {
            $query->whereNotNull('reference_number');
        } elseif ($request->filled('status') && $request->status === 'unassigned') {
            $query->whereNull('reference_number');
        }

        $students = $query->orderBy('full_name')->paginate(20)->withQueryString();

        return view('fees.reference-numbers.index', compact('students'));
    }

    /**
     * Show the bulk-upload form.
     */
    public function uploadForm()
    {
        return view('fees.reference-numbers.upload');
    }

    /**
     * Handle the bulk upload of index number -> reference number mappings.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('fees.reference-numbers.upload')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new ReferenceNumberImport();
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} record(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('fees.reference-numbers.index')->with('warning', $message);
            }

            return redirect()->route('fees.reference-numbers.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('fees.reference-numbers.upload')
                ->with('error', 'Error importing reference numbers: ' . $e->getMessage());
        }
    }

    /**
     * Download the reference number upload template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new ReferenceNumberTemplateExport, 'reference_numbers_template.xlsx');
    }
}
