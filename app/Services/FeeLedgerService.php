<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Student;
use Illuminate\Support\Collection;

class FeeLedgerService
{
    /**
     * Reference fee schedule for a student's programme: every configured fee
     * (per level, per academic year), regardless of whether the student has
     * actually reached that level/year yet.
     */
    public static function scheduleFor(Student $student): Collection
    {
        return FeeStructure::where('programme_id', $student->programme_id)
            ->with('academicYear')
            ->get()
            ->sortByDesc(fn (FeeStructure $fee) => $fee->academicYear->start_date ?? null)
            ->values();
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
            $rows->push([
                'date' => $arrear->academicYear->start_date ?? $arrear->created_at,
                'description' => 'Arrears - ' . ($arrear->academicYear->name ?? 'N/A'),
                'debit' => (float) $arrear->amount,
                'credit' => null,
                'academic_year' => $arrear->academicYear->name ?? 'N/A',
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
