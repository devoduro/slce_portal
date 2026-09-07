<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\StudentLevelHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FeeLedgerService
{
    /**
     * Resolve, in bulk, the level each student actually held during a given academic year.
     *
     * Every fee figure in the system hangs off this: a tuition bill is looked up by
     * programme + level + year, so asking "what level is this student?" instead of "what level
     * was this student that year?" silently re-prices every past year the moment a student is
     * promoted. Resolution order (single-student equivalent: Student::levelForAcademicYear()):
     *   1. An explicit snapshot for that year, written when the student was promoted out of it.
     *   2. The current academic year - not promoted out of it yet, so students.level still holds.
     *   3. The majority level of the courses they registered for that year.
     *   4. Stepped back from the nearest level we do know (a later snapshot, else where they
     *      stand today), one level per academic year in between.
     * Deliberately never falls through to the raw current level for a past year: for anyone
     * who has been promoted since, that is guaranteed to be the wrong answer.
     *
     * @param  Collection<int, Student>  $students
     * @return array<int, int|null> student_id => level held that year
     */
    public static function levelsFor(Collection $students, AcademicYear $year): array
    {
        $studentIds = $students->pluck('id')->all();

        if (empty($studentIds)) {
            return [];
        }

        $levels = array_fill_keys($studentIds, null);

        $snapshots = StudentLevelHistory::whereIn('student_id', $studentIds)
            ->where('academic_year_id', $year->id)
            ->pluck('level', 'student_id');

        $currentYear = AcademicYear::where('is_current', true)->first();
        $isCurrentYear = !$currentYear || (int) $currentYear->id === (int) $year->id;

        $inferred = [];

        if (!$isCurrentYear) {
            $courseLevels = DB::table('registrations')
                ->join('courses', 'courses.id', '=', 'registrations.course_id')
                ->whereIn('registrations.student_id', $studentIds)
                ->where('registrations.academic_year_id', $year->id)
                ->whereNotNull('courses.level')
                ->selectRaw('registrations.student_id, courses.level, COUNT(*) as cnt')
                ->groupBy('registrations.student_id', 'courses.level')
                ->orderByDesc('cnt')
                ->get();

            foreach ($courseLevels as $row) {
                // Ordered by count desc, so the first row seen for a student is their majority level.
                $inferred[$row->student_id] ??= (int) $row->level;
            }
        }

        $needsStepBack = collect();

        foreach ($students as $student) {
            if (isset($snapshots[$student->id])) {
                $levels[$student->id] = (int) $snapshots[$student->id];
                continue;
            }

            if ($isCurrentYear) {
                $levels[$student->id] = $student->level !== null ? (int) $student->level : null;
                continue;
            }

            if (isset($inferred[$student->id])) {
                $levels[$student->id] = $inferred[$student->id];
                continue;
            }

            $needsStepBack->push($student);
        }

        if ($needsStepBack->isEmpty()) {
            return $levels;
        }

        // Only the students with neither a snapshot nor course evidence need their other years
        // looked up - typically a handful, so this stays off the hot path for a full fee list.
        $otherSnapshots = StudentLevelHistory::whereIn('student_id', $needsStepBack->pluck('id'))
            ->get()
            ->groupBy('student_id');

        // Chronological position of every academic year, so "how many years back" is a
        // subtraction rather than date arithmetic.
        $positions = array_flip(AcademicYear::orderBy('start_date')->pluck('id')->all());

        foreach ($needsStepBack as $student) {
            $levels[$student->id] = self::steppedBackLevel(
                $student,
                $year,
                $positions,
                $otherSnapshots->get($student->id),
                $currentYear
            );
        }

        return $levels;
    }

    /**
     * Best-effort level for a past year we have no snapshot or registration evidence for:
     * anchor on the nearest level we do know and walk back one level per academic year.
     *
     * @param  array<int, int>  $positions  academic_year_id => chronological index
     * @param  Collection<int, StudentLevelHistory>|null  $snapshots  this student's snapshots
     */
    private static function steppedBackLevel(
        Student $student,
        AcademicYear $year,
        array $positions,
        ?Collection $snapshots,
        ?AcademicYear $currentYear
    ): ?int {
        $target = $positions[$year->id] ?? null;

        if ($target === null) {
            return null;
        }

        $anchorLevel = null;
        $anchorIndex = null;

        foreach ($snapshots ?? collect() as $snapshot) {
            $index = $positions[$snapshot->academic_year_id] ?? null;

            if ($index === null || $index <= $target) {
                continue;
            }

            if ($anchorIndex === null || $index < $anchorIndex) {
                $anchorIndex = $index;
                $anchorLevel = (int) $snapshot->level;
            }
        }

        if ($anchorIndex === null && $student->level !== null) {
            // No later snapshot - anchor on where the student stands today instead: a graduate's
            // level column is frozen at the terminal level they left on, so their graduation year
            // is the year that level belongs to; everyone else is at their level right now.
            $anchorYearId = $student->graduated_academic_year_id ?? $currentYear?->id;
            $anchorIndex = $anchorYearId !== null ? ($positions[$anchorYearId] ?? null) : null;
            $anchorLevel = (int) $student->level;
        }

        if ($anchorIndex === null || $anchorLevel === null) {
            return $student->level !== null ? (int) $student->level : null;
        }

        $stepped = $anchorLevel - (100 * ($anchorIndex - $target));

        // Never below Level 100, and never above the level we anchored on - a student cannot
        // have been further along earlier than they are later.
        return (int) min($anchorLevel, max(100, $stepped));
    }

    /**
     * Bulk-compute what each student owes coming INTO a given academic year: everything billed
     * in prior years (tuition + arrears + one-off charges) minus everything paid in those years.
     *
     * This is what the "Arrears" figure on a year-scoped fee list should be. The raw
     * student_arrears table is only ever an opening-balance upload - it is never reduced when
     * the student later pays that debt off, so summing it raw reports debt that has already
     * been settled. Mirrors the arithmetic of ledgerFor() so both agree, but resolved set-based
     * (a fixed handful of queries) rather than per student, since fee lists run over thousands.
     *
     * @param  Collection<int, Student>  $students
     * @return array<int, float> student_id => carried-forward balance (positive = still owed)
     */
    public static function carryForwardFor(Collection $students, AcademicYear $year): array
    {
        $studentIds = $students->pluck('id')->all();

        if (empty($studentIds)) {
            return [];
        }

        $balances = array_fill_keys($studentIds, 0.0);

        $priorYearIds = AcademicYear::where('start_date', '<', $year->start_date)->pluck('id')->all();

        if (empty($priorYearIds)) {
            return $balances;
        }

        $sumPerStudent = fn (string $table) => DB::table($table)
            ->whereIn('student_id', $studentIds)
            ->whereIn('academic_year_id', $priorYearIds)
            ->selectRaw('student_id, SUM(amount) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $payments = $sumPerStudent('student_payments');
        $arrears = $sumPerStudent('student_arrears');
        $charges = $sumPerStudent('student_fee_charges');

        // Which prior years each student was actually billed for. Enrolment (a course
        // registration) is what raises a tuition charge - NOT whether the student happened to
        // transact that year. Keying off transactions alone would let a student who registered
        // and simply never paid appear debt-free, while still not billing anyone for a year
        // they weren't enrolled in. Mirrored in ledgerFor() so both agree.
        $activeYears = [];

        foreach (['student_payments', 'student_arrears', 'student_fee_charges', 'registrations'] as $table) {
            $pairs = DB::table($table)
                ->whereIn('student_id', $studentIds)
                ->whereIn('academic_year_id', $priorYearIds)
                ->select('student_id', 'academic_year_id')
                ->distinct()
                ->get();

            foreach ($pairs as $pair) {
                $activeYears[$pair->student_id][$pair->academic_year_id] = true;
            }
        }

        $structures = FeeStructure::whereIn('academic_year_id', $priorYearIds)
            ->where('category', 'tuition')
            ->get();

        // The level each student held during each prior year, resolved the same way as
        // everywhere else - never their current level, which a promotion has already moved on.
        $levelsByYear = [];

        foreach (AcademicYear::whereIn('id', $priorYearIds)->get() as $priorYear) {
            $levelsByYear[$priorYear->id] = self::levelsFor($students, $priorYear);
        }

        foreach ($students as $student) {
            $balance = (float) ($arrears[$student->id] ?? 0)
                + (float) ($charges[$student->id] ?? 0)
                - (float) ($payments[$student->id] ?? 0);

            foreach (array_keys($activeYears[$student->id] ?? []) as $yearId) {
                $level = $levelsByYear[$yearId][$student->id] ?? null;

                $structure = $structures->first(fn (FeeStructure $s) => (int) $s->academic_year_id === (int) $yearId
                        && (int) $s->programme_id === (int) $student->programme_id
                        && $s->level !== null && $level !== null && (int) $s->level === (int) $level)
                    ?? $structures->first(fn (FeeStructure $s) => (int) $s->academic_year_id === (int) $yearId
                        && (int) $s->programme_id === (int) $student->programme_id
                        && $s->level === null);

                if ($structure) {
                    $balance += (float) $structure->amount;
                }
            }

            $balances[$student->id] = round($balance, 2);
        }

        return $balances;
    }

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
        $feeCharges = $student->feeCharges()->with('academicYear')->get();

        // Years the student was enrolled in count too, not just years they transacted in -
        // otherwise a year they registered for but never paid a cedi towards produces no
        // tuition row at all, and the statement understates what they owe. Matches the same
        // rule in carryForwardFor().
        $enrolledYearIds = $student->registrations()->distinct()->pluck('academic_year_id');

        $relevantYearIds = $payments->pluck('academic_year_id')
            ->merge($arrears->pluck('academic_year_id'))
            ->merge($feeCharges->pluck('academic_year_id'))
            ->merge($enrolledYearIds)
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

        foreach ($feeCharges as $charge) {
            $yearName = $charge->academicYear->name ?? 'N/A';

            $rows->push([
                'date' => $charge->created_at,
                'description' => $charge->categoryLabel() . ' - ' . $yearName,
                'debit' => (float) $charge->amount,
                'credit' => null,
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
                // Only payment rows are backed by an editable StudentPayment record - tuition
                // fee and arrears rows are derived figures, not something a CRUD form applies to.
                // The raw academic_year_id/reference_number are carried too so an edit form can
                // be pre-filled without a second lookup.
                'payment_id' => $payment->id,
                'academic_year_id' => $payment->academic_year_id,
                'reference_number' => $payment->reference_number,
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
