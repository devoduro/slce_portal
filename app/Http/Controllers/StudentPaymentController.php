<?php

namespace App\Http\Controllers;

use App\Exports\StudentFeesExport;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Programme;
use App\Models\Student;
use App\Models\StudentArrear;
use App\Models\StudentFeeCharge;
use App\Models\StudentPayment;
use App\Services\FeeLedgerService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class StudentPaymentController extends Controller
{
    /**
     * Display a listing of students with their fee status for a selected academic year.
     */
    public function index(Request $request)
    {
        [$academicYear, $rows] = $this->buildFeeRows($request);

        $academicYears = AcademicYear::chronological()->get();
        $programmes = Programme::orderBy('name')->get();
        $levels = Student::whereNotNull('level')->distinct()->orderBy('level')->pluck('level');

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $students = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page')]
        );

        return view('fees.payments.index', compact('students', 'academicYear', 'academicYears', 'programmes', 'levels'));
    }

    /**
     * Export the current filtered fee list to Excel.
     */
    public function exportExcel(Request $request)
    {
        [$academicYear, $rows] = $this->buildFeeRows($request);

        $filename = 'student-fees' . ($academicYear ? '-' . str_replace('/', '-', $academicYear->name) : '') . '.xlsx';

        return Excel::download(new StudentFeesExport($rows), $filename);
    }

    /**
     * Export the current filtered fee list to PDF.
     */
    public function exportPdf(Request $request)
    {
        [$academicYear, $rows] = $this->buildFeeRows($request);

        $filename = 'student-fees' . ($academicYear ? '-' . str_replace('/', '-', $academicYear->name) : '') . '.pdf';

        $pdf = PDF::loadView('fees.payments.export-pdf', compact('rows', 'academicYear'))->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Build the filtered, balance-annotated student fee rows shared by the on-screen
     * list and both exports. Balance is unclamped (fee due minus paid) so an overpayment
     * shows as negative - a creditor - rather than flooring at zero.
     *
     * @return array{0: ?AcademicYear, 1: \Illuminate\Support\Collection}
     */
    protected function buildFeeRows(Request $request): array
    {
        $academicYear = $request->filled('academic_year_id')
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $query = Student::with('programme');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('full_name', 'like', "%{$searchTerm}%")
                    ->orWhere('index_number', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('programme_id')) {
            $query->where('programme_id', $request->programme_id);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $students = $query->orderBy('full_name')->get();
        $studentIds = $students->pluck('id');

        // Tuition category specifically - a programme/level can also have graduation/resit fee
        // structures now, which must never be picked up here (they're separate one-off charges,
        // not part of the headline "Fee Amount" shown on this list).
        $feeStructures = $academicYear
            ? FeeStructure::where('academic_year_id', $academicYear->id)->where('category', 'tuition')->get()
            : collect();

        $paymentSums = $academicYear
            ? StudentPayment::where('academic_year_id', $academicYear->id)
                ->whereIn('student_id', $studentIds)
                ->selectRaw('student_id, SUM(amount) as total')
                ->groupBy('student_id')
                ->pluck('total', 'student_id')
            : collect();

        // Additional tuition-category charges (e.g. a fee correction uploaded via the fee
        // charges tool) fold into "Fee Amount" alongside the base structure; graduation/resit/
        // other categories deliberately don't, matching Student::tuitionFeeAmount().
        $tuitionChargeSums = $academicYear
            ? StudentFeeCharge::where('academic_year_id', $academicYear->id)
                ->where('category', 'tuition')
                ->whereIn('student_id', $studentIds)
                ->selectRaw('student_id, SUM(amount) as total')
                ->groupBy('student_id')
                ->pluck('total', 'student_id')
            : collect();

        $arrearSums = StudentArrear::whereIn('student_id', $studentIds)
            ->selectRaw('student_id, SUM(amount) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $rows = $students->map(function (Student $student) use ($academicYear, $feeStructures, $paymentSums, $tuitionChargeSums, $arrearSums) {
            $structure = null;
            $paid = 0.0;
            $balance = 0.0;
            $percentage = 0.0;
            $feeAmount = null;
            $arrears = (float) ($arrearSums[$student->id] ?? 0);

            if ($academicYear) {
                $structure = $feeStructures->first(fn (FeeStructure $f) => (int) $f->programme_id === (int) $student->programme_id && (int) $f->level === (int) $student->level)
                    ?? $feeStructures->first(fn (FeeStructure $f) => (int) $f->programme_id === (int) $student->programme_id && $f->level === null);

                $paid = (float) ($paymentSums[$student->id] ?? 0);
                $chargesAmount = (float) ($tuitionChargeSums[$student->id] ?? 0);
                $structureAmount = $structure ? (float) $structure->amount : 0.0;
                $hasFeeInfo = $structure || $chargesAmount > 0;
                $rawBill = $structureAmount + $chargesAmount;

                // "Fee Amount" nets out arrears (a prior credit reduces it, a prior debt tops it
                // up) so it reads as the actual amount due this year, not just the raw tuition
                // bill sitting next to an unrelated-looking arrears figure.
                $netFeeAmount = $rawBill + $arrears;
                $feeAmount = $hasFeeInfo ? $netFeeAmount : null;

                // Balance is fee due (net of arrears) minus what's been paid - unchanged in
                // value from before, just expressed via the netted fee amount above.
                $balance = $netFeeAmount - $paid;
                $percentage = $hasFeeInfo
                    ? ($netFeeAmount > 0 ? round(($paid / $netFeeAmount) * 100, 1) : 100.0)
                    : 0.0;
            }

            return [
                'student' => $student,
                'fee_amount' => $feeAmount,
                'paid' => $paid,
                'balance' => $balance,
                'percentage' => $percentage,
                'arrears' => $arrears,
                'status' => $balance < 0 ? 'creditor' : ($balance > 0 ? 'debtor' : 'settled'),
            ];
        });

        if ($request->filled('status')) {
            if ($request->status === 'creditors') {
                $rows = $rows->filter(fn (array $r) => $r['status'] === 'creditor');
            } elseif ($request->status === 'debtors') {
                $rows = $rows->filter(fn (array $r) => $r['status'] === 'debtor');
            }
        }

        return [$academicYear, $rows->values()];
    }

    /**
     * Show a student's payment ledger and fee status for a selected academic year.
     */
    public function show(Request $request, Student $student)
    {
        $academicYear = $request->filled('academic_year_id')
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $academicYears = AcademicYear::chronological()->get();

        $feeStructure = $academicYear ? $student->applicableFeeStructure($academicYear) : null;
        // The base structure amount plus any tuition-category charges billed on top of it -
        // the same figure shown as "Fee Amount" on the /fees list, kept consistent here.
        $feeAmount = $academicYear ? $student->tuitionFeeAmount($academicYear) : 0.0;
        $totalPaid = $academicYear ? $student->totalPaid($academicYear) : 0;
        $percentage = $academicYear ? $student->paymentPercentage($academicYear) : 0;
        $totalArrears = $student->totalArrears();

        $ledger = FeeLedgerService::ledgerFor($student);

        // Sourced from the ledger itself (not recomputed independently) so this figure can
        // never drift from the transaction math shown in the statement below.
        $balanceDue = $ledger[0]['balance'] ?? 0.0;

        return view('fees.payments.show', compact(
            'student',
            'academicYear',
            'academicYears',
            'feeStructure',
            'feeAmount',
            'totalPaid',
            'percentage',
            'totalArrears',
            'ledger',
            'balanceDue'
        ));
    }

    /**
     * Printable statement of account (full ledger) for a student - a standalone
     * letterhead document, independent of the academic year currently selected on screen.
     */
    public function printLedger(Student $student)
    {
        $ledger = FeeLedgerService::ledgerFor($student);

        // ledgerFor() returns newest-first (for the on-screen statement); the printed
        // statement reads top-to-bottom oldest-first instead, so the balance builds down
        // the page and the most recent transaction lands at the bottom, not the top.
        $balanceDue = $ledger[0]['balance'] ?? 0.0;
        $ledger = array_reverse($ledger);

        $settings = \Illuminate\Support\Facades\DB::table('settings')->where('category', 'institution')->pluck('value', 'key')->toArray();

        return view('fees.payments.print-ledger', compact('student', 'ledger', 'balanceDue', 'settings'));
    }

    /**
     * Record a new payment for a student.
     */
    public function store(Request $request, Student $student)
    {
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer,cheque,other',
            'bank' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $student->payments()->create([
            'academic_year_id' => $request->academic_year_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'bank' => $request->bank,
            'payment_date' => $request->payment_date,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'recorded_by' => auth()->id(),
        ]);

        return redirect()->route('fees.show', ['student' => $student->id, 'academic_year_id' => $request->academic_year_id])
            ->with('success', 'Payment recorded successfully.');
    }

    /**
     * Aggregate fees report: expected vs collected fees by programme and level
     * for a selected academic year, for admin/accountant oversight.
     */
    public function report(Request $request)
    {
        $academicYear = $request->filled('academic_year_id')
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $academicYears = AcademicYear::chronological()->get();

        [$rows, $grandExpected, $grandCollected] = $this->buildReportRows($academicYear);

        return view('fees.report', compact('academicYear', 'academicYears', 'rows', 'grandExpected', 'grandCollected'));
    }

    /**
     * Printable version of the fees report (standalone letterhead document).
     */
    public function printReport(Request $request)
    {
        $academicYear = $request->filled('academic_year_id')
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        abort_unless($academicYear, 404, 'No academic year selected to print.');

        [$rows, $grandExpected, $grandCollected] = $this->buildReportRows($academicYear);

        $settings = \Illuminate\Support\Facades\DB::table('settings')->where('category', 'institution')->pluck('value', 'key')->toArray();

        return view('fees.report-print', compact('academicYear', 'rows', 'grandExpected', 'grandCollected', 'settings'));
    }

    /**
     * Build the programme/level breakdown rows (+ grand totals) for a fees report,
     * shared by the on-screen report and its printable version.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: float, 2: float}
     */
    protected function buildReportRows(?AcademicYear $academicYear): array
    {
        $rows = collect();
        $grandExpected = 0.0;
        $grandCollected = 0.0;

        if (!$academicYear) {
            return [$rows, $grandExpected, $grandCollected];
        }

        $programmes = Programme::orderBy('name')->get();

        foreach ($programmes as $programme) {
            $levels = Student::where('programme_id', $programme->id)
                ->whereNotNull('level')
                ->distinct()
                ->orderBy('level')
                ->pluck('level');

            foreach ($levels as $level) {
                $studentIds = Student::where('programme_id', $programme->id)
                    ->where('level', $level)
                    ->pluck('id');

                if ($studentIds->isEmpty()) {
                    continue;
                }

                $feeStructure = FeeStructure::where('academic_year_id', $academicYear->id)
                    ->where('programme_id', $programme->id)
                    ->where('level', $level)
                    ->first()
                    ?? FeeStructure::where('academic_year_id', $academicYear->id)
                        ->where('programme_id', $programme->id)
                        ->whereNull('level')
                        ->first();

                $studentCount = $studentIds->count();
                $expected = $feeStructure ? (float) $feeStructure->amount * $studentCount : 0.0;
                $collected = (float) StudentPayment::whereIn('student_id', $studentIds)
                    ->where('academic_year_id', $academicYear->id)
                    ->sum('amount');

                $rows->push([
                    'programme' => $programme->name,
                    'level' => $level,
                    'students' => $studentCount,
                    'fee_amount' => $feeStructure?->amount,
                    'expected' => $expected,
                    'collected' => $collected,
                    'balance' => $expected - $collected,
                    'percentage' => $expected > 0 ? round(($collected / $expected) * 100, 1) : 0,
                ]);

                $grandExpected += $expected;
                $grandCollected += $collected;
            }
        }

        return [$rows, $grandExpected, $grandCollected];
    }

    /**
     * Remove a mis-entered payment record.
     */
    public function destroy(StudentPayment $payment)
    {
        $payment->delete();

        // back() rather than a hardcoded route - this same endpoint is used from both the
        // per-student ledger and the bulk payments list, and each wants to stay on its own page.
        return back()->with('success', 'Payment removed successfully.');
    }

    /**
     * Update an existing payment record.
     */
    public function update(Request $request, StudentPayment $payment)
    {
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer,cheque,other',
            'bank' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'edit_payment_' . $payment->id)->withInput();
        }

        $payment->update([
            'academic_year_id' => $request->academic_year_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'bank' => $request->bank,
            'payment_date' => $request->payment_date,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
        ]);

        return redirect()->route('fees.show', ['student' => $payment->student_id, 'academic_year_id' => $request->academic_year_id])
            ->with('success', 'Payment updated successfully.');
    }
}
