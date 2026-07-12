<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Support\Collection;

class FeeLedgerService
{
    /**
     * The fee that applies to a student right now: their current level, for the
     * current academic year. Delegates to Student::applicableFeeStructure() so this
     * always matches the same figure used for balance/percentage calculations elsewhere.
     */
    public static function scheduleFor(Student $student): Collection
    {
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();

        if (!$currentAcademicYear) {
            return collect();
        }

        $feeStructure = $student->applicableFeeStructure($currentAcademicYear);

        return $feeStructure ? collect([$feeStructure]) : collect();
    }

    /**
     * Build a flat, chronological Debit/Credit/Balance statement of account for a student,
     * covering every academic year they have actual activity in (a payment or an arrears
     * entry) plus the current academic year (so an unpaid current-year charge still shows).
     * Returned newest-first, each row carrying the running balance as of that transaction.
     *
     * @return array<int, array{date: \Carbon\Carbon, description: string, debit: ?float, credit: ?float, academic_year: string, bank: ?string, payment_mode: ?string, balance: float}>
     */
    public static function ledgerFor(Student $student): array
    {
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();

        $payments = $student->payments()->with('academicYear')->get();
        $arrears = $student->arrears()->with('academicYear')->get();

        $relevantYearIds = $payments->pluck('academic_year_id')
            ->merge($arrears->pluck('academic_year_id'))
            ->when($currentAcademicYear, fn (Collection $ids) => $ids->push($currentAcademicYear->id))
            ->unique()
            ->filter();

        $academicYears = AcademicYear::whereIn('id', $relevantYearIds)->get();

        $rows = collect();

        foreach ($academicYears as $year) {
            $feeStructure = $student->applicableFeeStructure($year);

            if ($feeStructure) {
                $rows->push([
                    'date' => $year->start_date,
                    'description' => 'Tuition Fee - ' . $year->name,
                    'debit' => (float) $feeStructure->amount,
                    'credit' => null,
                    'academic_year' => $year->name,
                    'bank' => null,
                    'payment_mode' => null,
                ]);
            }
        }

        foreach ($arrears as $arrear) {
            $amount = (float) $arrear->amount;
            $yearName = $arrear->academicYear->name ?? 'N/A';

            // A negative arrear means the school owes the student (e.g. an overpayment) -
            // show it as a credit rather than a negative debit. Dated by when the arrear was
            // actually recorded (not the academic year's nominal start date), so the statement
            // reflects when the transaction really happened.
            $rows->push([
                'date' => $arrear->created_at,
                'description' => $amount >= 0 ? "Arrears - {$yearName}" : "Overpayment Credit - {$yearName}",
                'debit' => $amount > 0 ? $amount : null,
                'credit' => $amount < 0 ? abs($amount) : null,
                'academic_year' => $yearName,
                'bank' => null,
                'payment_mode' => null,
            ]);
        }

        foreach ($payments as $payment) {
            $rows->push([
                'date' => $payment->payment_date,
                'description' => 'Payment Received',
                'debit' => null,
                'credit' => (float) $payment->amount,
                'academic_year' => $payment->academicYear->name ?? 'N/A',
                'bank' => $payment->bank,
                'payment_mode' => $payment->payment_method,
            ]);
        }

        $balance = 0.0;

        $chronological = $rows->sortBy('date')->values()->map(function (array $row) use (&$balance) {
            $balance += $row['debit'] ?? 0;
            $balance -= $row['credit'] ?? 0;
            $row['balance'] = $balance;

            return $row;
        });

        return $chronological->reverse()->values()->all();
    }
}
