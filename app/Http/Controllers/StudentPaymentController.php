<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Programme;
use App\Models\Student;
use App\Models\StudentPayment;
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

        return view('fees.payments.index', compact('students', 'academicYear', 'academicYears', 'programmes'));
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

        return view('fees.payments.show', compact(
            'student',
            'academicYear',
            'academicYears',
            'feeStructure',
            'totalPaid',
            'balance',
            'percentage',
            'payments'
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
            'payment_date' => $request->payment_date,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'recorded_by' => auth()->id(),
        ]);

        return redirect()->route('fees.show', ['student' => $student->id, 'academic_year_id' => $request->academic_year_id])
            ->with('success', 'Payment recorded successfully.');
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
