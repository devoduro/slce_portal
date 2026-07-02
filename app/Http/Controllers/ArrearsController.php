<?php

namespace App\Http\Controllers;

use App\Exports\ArrearsTemplateExport;
use App\Imports\ArrearsImport;
use App\Models\AcademicYear;
use App\Models\StudentArrear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ArrearsController extends Controller
{
    /**
     * Display a listing of uploaded arrears records.
     */
    public function index(Request $request)
    {
        $query = StudentArrear::with(['student', 'academicYear']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('index_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $arrears = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $academicYears = AcademicYear::chronological()->get();

        return view('fees.arrears.index', compact('arrears', 'academicYears'));
    }

    /**
     * Show the bulk-upload form.
     */
    public function uploadForm()
    {
        return view('fees.arrears.upload');
    }

    /**
     * Handle the bulk upload of a debtors list.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('fees.arrears.upload')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new ArrearsImport();
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} record(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('fees.arrears.index')->with('warning', $message);
            }

            return redirect()->route('fees.arrears.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('fees.arrears.upload')
                ->with('error', 'Error importing debtors list: ' . $e->getMessage());
        }
    }

    /**
     * Download the debtors list template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new ArrearsTemplateExport, 'arrears_template.xlsx');
    }

    /**
     * Remove a mistaken arrears entry.
     */
    public function destroy(StudentArrear $arrear)
    {
        $arrear->delete();

        return redirect()->route('fees.arrears.index')
            ->with('success', 'Arrears record removed successfully.');
    }
}
