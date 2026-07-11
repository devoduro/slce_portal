<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Programme;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Services\FeeLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentPaymentController extends Controller
{
    /**
     * Display a listing of students with their fee status for a selected academic year.
     */
    public function index(Request $request)
    {
        $academicYear = $request->filled('academic_year_id')
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $academicYears = AcademicYear::chronological()->get();
        $programmes = Programme::orderBy('name')->get();
        $levels = Student::whereNotNull('level')->distinct()->orderBy('level')->pluck('level');

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

        $students = $query->orderBy('full_name')->paginate(20)->withQueryString();

        return view('fees.payments.index', compact('students', 'academicYear', 'academicYears', 'programmes', 'levels'));
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
        $totalPaid = $academicYear ? $student->totalPaid($academicYear) : 0;
        $balance = $academicYear ? $student->feeBalance($academicYear) : 0;
        $percentage = $academicYear ? $student->paymentPercentage($academicYear) : 0;

        $payments = $academicYear
            ? $student->payments()->where('academic_year_id', $academicYear->id)->orderByDesc('payment_date')->get()
            : collect();

        $arrears = $student->arrears()->with('academicYear')->orderByDesc('academic_year_id')->get();
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
            'totalPaid',
            'balance',
            'percentage',
            'payments',
            'arrears',
            'totalArrears',
            'ledger',
            'balanceDue'
        ));
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
        $studentId = $payment->student_id;
        $academicYearId = $payment->academic_year_id;

        $payment->delete();

        return redirect()->route('fees.show', ['student' => $studentId, 'academic_year_id' => $academicYearId])
            ->with('success', 'Payment removed successfully.');
    }
}
