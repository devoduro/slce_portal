<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Services\FeeLedgerService;
use Illuminate\Support\Facades\Auth;

class StudentFeeController extends Controller
{
    /**
     * Show the authenticated student's fee schedule and full statement of account.
     */
    public function index()
    {
        $student = Auth::user()->student;

        $schedule = FeeLedgerService::scheduleFor($student);
        $ledger = FeeLedgerService::ledgerFor($student);

        $currentAcademicYear = AcademicYear::where('is_current', true)->first();
        $currentYearBalance = $currentAcademicYear ? $student->feeBalance($currentAcademicYear) : 0.0;

        // Sourced from the ledger itself (not recomputed independently) so this figure can
        // never drift from the transaction math shown below it - it's the running balance
        // as of the most recent transaction, i.e. arrears + every unpaid year's tuition combined.
        $balanceDue = $ledger[0]['balance'] ?? 0.0;

        // The closing balance of the previous year(s), computed the same way as the "Arrears"
        // column the finance office sees on /fees, so a student is never shown a different
        // arrears figure from the one the college is working off. Adds back to Total Balance Due
        // together with the current year's own balance above.
        $totalArrears = $student->totalArrears();

        return view('student.fees.index', compact(
            'student', 'schedule', 'ledger', 'totalArrears', 'currentYearBalance', 'balanceDue'
        ));
    }
}
