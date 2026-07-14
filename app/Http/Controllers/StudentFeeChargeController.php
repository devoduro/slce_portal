<?php

namespace App\Http\Controllers;

use App\Exports\StudentFeeChargeTemplateExport;
use App\Imports\StudentFeeChargeImport;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\StudentFeeCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class StudentFeeChargeController extends Controller
{
    /**
     * Display a listing of specific fee charges billed directly to students.
     */
    public function index(Request $request)
    {
        $query = StudentFeeCharge::with(['student', 'academicYear']);

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

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $charges = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $academicYears = AcademicYear::chronological()->get();
        $categories = FeeCategory::orderBy('name')->get();

        return view('fees.charges.index', compact('charges', 'academicYears', 'categories'));
    }

    /**
     * Show the bulk-upload form.
     */
    public function uploadForm()
    {
        return view('fees.charges.upload');
    }

    /**
     * Handle the bulk upload of specific student fee charges.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('fees.charges.upload')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new StudentFeeChargeImport();
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} record(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('fees.charges.index')->with('warning', $message);
            }

            return redirect()->route('fees.charges.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('fees.charges.upload')
                ->with('error', 'Error importing fee charges: ' . $e->getMessage());
        }
    }

    /**
     * Download the fee charges upload template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new StudentFeeChargeTemplateExport, 'student_fee_charges_template.xlsx');
    }

    /**
     * Remove a mistaken fee charge entry.
     */
    public function destroy(StudentFeeCharge $charge)
    {
        $charge->delete();

        return redirect()->route('fees.charges.index')
            ->with('success', 'Fee charge removed successfully.');
    }
}
