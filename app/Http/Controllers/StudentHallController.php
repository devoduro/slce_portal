<?php

namespace App\Http\Controllers;

use App\Exports\HallTemplateExport;
use App\Imports\HallImport;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class StudentHallController extends Controller
{
    /**
     * Display a listing of students grouped/filterable by hall, with per-hall counts.
     */
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

        $students = $query->orderBy('hall')->orderBy('full_name')->paginate(20)->withQueryString();

        $hallCounts = Student::whereNotNull('hall')->where('hall', '!=', '')
            ->select('hall', DB::raw('count(*) as total'))
            ->groupBy('hall')
            ->orderBy('hall')
            ->get();

        $unassignedCount = Student::whereNull('hall')->orWhere('hall', '')->count();

        return view('student-halls.index', compact('students', 'hallCounts', 'unassignedCount'));
    }

    /**
     * Show the bulk-upload form.
     */
    public function uploadForm()
    {
        return view('student-halls.upload');
    }

    /**
     * Handle the bulk upload of index number -> hall mappings.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('student-halls.upload')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new HallImport();
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} record(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('student-halls.index')->with('warning', $message);
            }

            return redirect()->route('student-halls.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('student-halls.upload')
                ->with('error', 'Error importing halls: ' . $e->getMessage());
        }
    }

    /**
     * Download the hall upload template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new HallTemplateExport, 'student_halls_template.xlsx');
    }

    /**
     * Printable list of students, respecting the current hall/search filters.
     */
    public function print(Request $request)
    {
        $students = $this->filteredQuery($request)->orderBy('hall')->orderBy('full_name')->get();

        $hall = $request->hall;

        return view('student-halls.print', compact('students', 'hall'));
    }

    /**
     * Build the filtered student query shared by the on-screen list and the print view.
     */
    protected function filteredQuery(Request $request)
    {
        $query = Student::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('index_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('hall')) {
            $query->where('hall', $request->hall);
        } elseif ($request->filled('status') && $request->status === 'unassigned') {
            $query->where(function ($q) {
                $q->whereNull('hall')->orWhere('hall', '');
            });
        }

        return $query;
    }
}
