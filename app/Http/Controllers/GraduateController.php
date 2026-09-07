<?php

namespace App\Http\Controllers;

use App\Exports\GraduatesExport;
use App\Models\AcademicYear;
use App\Models\Programme;
use App\Models\Student;
use App\Services\FeeLedgerService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The graduate register: everyone who has completed their programme, what they left with, and -
 * the part the finance office actually chases - what they still owe.
 *
 * A graduate falls out of every year-scoped screen in the system, because they have no current
 * academic year to be listed under. Their debt does not disappear with them, so it needs a home
 * of its own rather than being findable only by paging through the fee list of a year gone by.
 */
class GraduateController extends Controller
{
    /**
     * The graduate register, filtered and annotated with each graduate's outstanding balance.
     */
    public function index(Request $request)
    {
        [$rows, $summary] = $this->buildGraduateRows($request);

        $programmes = Programme::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [25, 50, 100, 200], true)) {
            $perPage = 25;
        }

        $page = max(1, (int) $request->input('page', 1));

        $graduates = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('graduates.index', compact('graduates', 'summary', 'programmes', 'academicYears'));
    }

    /**
     * Export the current filtered register to Excel.
     */
    public function exportExcel(Request $request)
    {
        [$rows] = $this->buildGraduateRows($request);

        return Excel::download(new GraduatesExport($rows), 'graduates' . $this->filenameSuffix($request) . '.xlsx');
    }

    /**
     * Export the current filtered register to PDF.
     */
    public function exportPdf(Request $request)
    {
        [$rows, $summary] = $this->buildGraduateRows($request);

        $title = $request->input('status') === 'owing'
            ? 'Graduates With Outstanding Balances'
            : 'Graduate Register';

        $pdf = PDF::loadView('graduates.export-pdf', compact('rows', 'summary', 'title'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('graduates' . $this->filenameSuffix($request) . '.pdf');
    }

    /**
     * Build the filtered, balance-annotated graduate rows shared by the list and both exports,
     * plus the headline totals shown above them.
     *
     * @return array{0: Collection, 1: array<string, mixed>}
     */
    protected function buildGraduateRows(Request $request): array
    {
        $query = Student::with(['programme', 'graduatedAcademicYear'])
            ->where('status', 'graduated');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('index_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('programme_id')) {
            $query->where('programme_id', $request->programme_id);
        }

        if ($request->filled('graduated_academic_year_id')) {
            $query->where('graduated_academic_year_id', $request->graduated_academic_year_id);
        }

        $students = $query->orderBy('full_name')->get();

        // The whole account, every year of it - a graduate has no "current year" balance for a
        // year-scoped figure to live in, so anything less than the full ledger understates what
        // they left owing.
        $outstanding = FeeLedgerService::totalOutstandingFor($students);

        $rows = $students->map(function (Student $student) use ($outstanding) {
            $balance = round((float) ($outstanding[$student->id] ?? 0), 2);

            return [
                'student' => $student,
                'balance' => $balance,
                'status' => $balance > 0.01 ? 'owing' : ($balance < -0.01 ? 'credit' : 'settled'),
            ];
        });

        // Totals describe the filtered cohort before the debtor/settled filter narrows it, so
        // "142 of 314 owing" stays meaningful once you click through to the owing list.
        $summary = [
            'total' => $rows->count(),
            'owing_count' => $rows->where('status', 'owing')->count(),
            'owing_total' => round($rows->where('status', 'owing')->sum('balance'), 2),
            'credit_count' => $rows->where('status', 'credit')->count(),
            'credit_total' => round(abs($rows->where('status', 'credit')->sum('balance')), 2),
            'settled_count' => $rows->where('status', 'settled')->count(),
        ];

        if ($request->filled('status') && in_array($request->status, ['owing', 'settled', 'credit'], true)) {
            $rows = $rows->where('status', $request->status);
        }

        // Biggest debts first when looking at debtors - that is the order the office works in.
        $rows = $request->input('sort') === 'name'
            ? $rows->sortBy(fn (array $row) => $row['student']->full_name)
            : $rows->sortByDesc('balance');

        return [$rows->values(), $summary];
    }

    /**
     * Filename suffix reflecting the active filter, so downloaded files stay tellable apart.
     */
    protected function filenameSuffix(Request $request): string
    {
        return $request->input('status') === 'owing' ? '-owing' : '';
    }
}
