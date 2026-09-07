<?php

namespace App\Models;

use App\Services\FeeLedgerService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'index_number',
        'reference_number',
        'hall',
        'full_name',
        'date_of_birth',
        'gender',
        'programme_id',
        'level',
        'class_group_id',
        'status',
        'graduated_academic_year_id',
        'profile_photo',
        'emergency_contact_name',
        'emergency_contact_phone',
        'email',
        'phone',
        'address',
        'hometown',
        'gps_address',
        'emergency_contact_relationship',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
    ];
    
    /**
     * Get the programme that the student belongs to.
     */
    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    /**
     * Get the academic year the student graduated in (set when Promotions marks them graduated
     * at their programme's terminal level).
     */
    public function graduatedAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'graduated_academic_year_id');
    }

    /**
     * Human-friendly label for the student's academic standing. A graduated student no longer
     * belongs to a level - showing the last level they studied at ("Level 400") is misleading
     * once they've left, so this shows "Graduated" with the academic year they graduated in
     * instead (e.g. "Graduated (2025/2026)").
     */
    public function levelLabel(): string
    {
        if ($this->status === 'graduated') {
            return $this->graduatedAcademicYear
                ? "Graduated ({$this->graduatedAcademicYear->name})"
                : 'Graduated';
        }

        if ($this->status === 'withdrawn') {
            return 'Withdrawn';
        }

        return $this->level ? "Level {$this->level}" : 'N/A';
    }

    /**
     * Academic standing as at a particular academic year, for year-scoped screens like the fee
     * list. A graduate's level column stays frozen at the terminal level they left on, so
     * reading it raw brands them "Level 400" forever - but for the years they were still
     * studying, that level is exactly what they were billed at and what should be shown. So:
     * their level up to and including the year they graduated in, "Graduated" from then on.
     *
     * Pass $resolvedLevel when the caller has already resolved the year's level in bulk
     * (FeeLedgerService::levelsFor()) to avoid re-querying it per row.
     */
    public function levelLabelForYear(AcademicYear $academicYear, ?int $resolvedLevel = null): string
    {
        $resolvedLevel ??= $this->levelForAcademicYear($academicYear);

        if ($this->status === 'graduated') {
            $graduatedYear = $this->graduatedAcademicYear;

            if (!$graduatedYear) {
                return 'Graduated';
            }

            if ($academicYear->start_date > $graduatedYear->start_date) {
                return "Graduated ({$graduatedYear->name})";
            }

            return $resolvedLevel ? "Level {$resolvedLevel}" : 'Graduated';
        }

        if ($this->status === 'withdrawn') {
            return 'Withdrawn';
        }

        return $resolvedLevel ? "Level {$resolvedLevel}" : 'N/A';
    }

    /**
     * Get the class group the student is assigned to.
     */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /**
     * Get the snapshots of what level this student held during past academic years - taken at
     * the moment they're promoted out of each one. See levelForAcademicYear().
     */
    public function levelHistories(): HasMany
    {
        return $this->hasMany(StudentLevelHistory::class);
    }

    /**
     * Get the results for the student.
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }
    
    /**
     * Get the user account associated with the student.
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get the course registrations for the student.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get the fee payments made by the student.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(StudentPayment::class);
    }

    /**
     * Get the biometric registrations (semester check-ins) for the student.
     */
    public function biometricRegistrations(): HasMany
    {
        return $this->hasMany(BiometricRegistration::class);
    }

    /**
     * Get the arrears (debt carried forward from previous years) for the student.
     */
    public function arrears(): HasMany
    {
        return $this->hasMany(StudentArrear::class);
    }

    /**
     * Get the one-off fee charges (graduation fee, resit fee, etc.) billed directly to
     * this student, as opposed to the programme-wide fee structures.
     */
    public function feeCharges(): HasMany
    {
        return $this->hasMany(StudentFeeCharge::class);
    }

    /**
     * Get the STS/Internship placements for the student.
     */
    public function stsPlacements(): HasMany
    {
        return $this->hasMany(StsPlacement::class);
    }

    /**
     * Get the total outstanding debt carried into the current academic year from previous ones:
     * everything billed in prior years (tuition at the level held *that* year, arrears, one-off
     * charges) net of everything paid in them. Not a raw sum over the arrears table, which is an
     * opening-balance upload that is never reduced when the student later pays the debt off.
     *
     * Shares FeeLedgerService::carryForwardFor() with the /fees list and the accountant
     * dashboard, so the "Arrears" figure a student sees is the same one the office sees, and
     * arrears + this year's own balance always reconciles to the ledger's Total Balance Due.
     * This is informational only and does not affect the course-registration fee gate.
     */
    public function totalArrears(): float
    {
        $currentYear = AcademicYear::where('is_current', true)->first();

        if (!$currentYear) {
            return 0.0;
        }

        return (float) (FeeLedgerService::carryForwardFor(collect([$this]), $currentYear)[$this->id] ?? 0.0);
    }

    /**
     * Determine whether the student has completed biometric registration for a given semester.
     */
    public function hasBiometricVerification(Semester $semester): bool
    {
        return $this->biometricRegistrations()
            ->where('semester_id', $semester->id)
            ->exists();
    }

    /**
     * Get the level this student actually held during a given academic year - not necessarily
     * their current level, since a promotion bumps the level column for good, forgetting what
     * it used to be. Resolution order:
     *  1. An explicit snapshot for that year (taken when the student was later promoted out
     *     of it - see PromotionController) - authoritative when present.
     *  2. If this is the current academic year (or there's no "current" year concept at all),
     *     the student hasn't been promoted out of it yet, so their level column is still correct.
     *  3. Otherwise (a past year predating this tracking, with no snapshot) - best-effort:
     *     infer from the level of the courses they were actually registered for that year.
     *  4. Failing that, step back from the nearest level we do know, one level per academic
     *     year - never the raw current level, which is wrong for anyone promoted since.
     *
     * Single-student front end for FeeLedgerService::levelsFor(), so the per-student pages and
     * the bulk fee lists can never disagree about what level a student was billed at.
     */
    public function levelForAcademicYear(AcademicYear $academicYear): ?int
    {
        return FeeLedgerService::levelsFor(collect([$this]), $academicYear)[$this->id] ?? null;
    }

    /**
     * Get the fee structure that applies to this student for a given academic year and
     * category (defaults to the base tuition/school fee - the one that gates course
     * registration). Matches on programme + the level the student held *during that academic
     * year* (see levelForAcademicYear()) first, falling back to a programme-wide (null level)
     * entry.
     */
    public function applicableFeeStructure(AcademicYear $academicYear, string $category = 'tuition'): ?FeeStructure
    {
        $query = FeeStructure::where('academic_year_id', $academicYear->id)
            ->where('programme_id', $this->programme_id)
            ->where('category', $category);

        $level = $this->levelForAcademicYear($academicYear);

        if ($level !== null) {
            $structure = (clone $query)->where('level', $level)->first();
            if ($structure) {
                return $structure;
            }
        }

        return $query->whereNull('level')->first();
    }

    /**
     * Get the total amount the student has paid for a given academic year.
     */
    public function totalPaid(AcademicYear $academicYear): float
    {
        return (float) $this->payments()
            ->where('academic_year_id', $academicYear->id)
            ->sum('amount');
    }

    /**
     * Get the "school fee" figure for a given academic year - the base tuition fee structure
     * plus any additional tuition-category charges billed to this student that year (e.g. a
     * correction uploaded via the fee charges tool). Deliberately excludes graduation/resit/
     * other categories, which are separate one-off charges shown on their own in the ledger
     * rather than folded into the headline tuition figure shown across /fees and /student/fees.
     */
    public function tuitionFeeAmount(AcademicYear $academicYear): float
    {
        $structure = $this->applicableFeeStructure($academicYear);
        $structureAmount = $structure ? (float) $structure->amount : 0.0;

        $chargesAmount = (float) $this->feeCharges()
            ->where('academic_year_id', $academicYear->id)
            ->where('category', 'tuition')
            ->sum('amount');

        return $structureAmount + $chargesAmount;
    }

    /**
     * Get the student's outstanding balance *for a given academic year alone*: everything billed
     * in that year (tuition structure, one-off charges, any arrears row tagged to it) minus what
     * was paid in that year. Debt carried in from earlier years is deliberately NOT included -
     * that is totalArrears(), and the two add up to the ledger's Total Balance Due.
     *
     * Previously this added every arrears row the student had, from any year, on top of the
     * current year's bill while only subtracting the current year's payments. Since the arrears
     * table is an opening-balance upload that is never written down when the debt is settled,
     * that charged owing students for the same debt twice - once as a raw arrear here and again
     * as the unpaid prior-year balance the ledger works out for itself.
     *
     * Not floored at zero - an overpayment correctly reads as a negative balance, and a year
     * with no fee structure yet still shows any charges/arrears actually billed against it
     * rather than reporting a clean zero over the top of real debt.
     */
    public function feeBalance(AcademicYear $academicYear): float
    {
        $structure = $this->applicableFeeStructure($academicYear);

        $billed = ($structure ? (float) $structure->amount : 0.0)
            + (float) $this->feeCharges()->where('academic_year_id', $academicYear->id)->sum('amount')
            + (float) $this->arrears()->where('academic_year_id', $academicYear->id)->sum('amount');

        return round($billed - $this->totalPaid($academicYear), 2);
    }

    /**
     * Get the percentage of the applicable fee the student has paid for a given academic year.
     */
    public function paymentPercentage(AcademicYear $academicYear): float
    {
        $feeAmount = $this->tuitionFeeAmount($academicYear);

        if ($feeAmount <= 0) {
            return 0.0;
        }

        return round(($this->totalPaid($academicYear) / $feeAmount) * 100, 2);
    }

    /**
     * Determine whether the student has paid enough of their fees to register courses for the
     * given semester. Eligible when the student's Total Balance Due (the full ledger balance -
     * arrears, all years' tuition, graduation/resit charges, everything) is no more than the
     * portion of this year's bill left unpaid by the required percentage, i.e.
     * balanceDue <= billAmount - (billAmount * required%). Using the ledger-wide balance (not
     * just this year's tuition) means credit carried forward from previous years counts toward
     * meeting the threshold.
     */
    public function meetsRegistrationThreshold(Semester $semester): bool
    {
        $required = (float) ($semester->required_payment_percentage ?? 0);

        if ($required <= 0) {
            return true;
        }

        $billAmount = $this->tuitionFeeAmount($semester->academicYear);

        if ($billAmount <= 0) {
            return false;
        }

        $maxAllowedBalance = $billAmount - ($billAmount * $required / 100);
        $balanceDue = FeeLedgerService::ledgerFor($this)[0]['balance'] ?? 0.0;

        return $balanceDue <= $maxAllowedBalance;
    }

    /**
     * Calculate the student's CGPA.
     *
     * @return float
     */
    public function calculateCGPA(): float
    {
        $results = $this->results;

        if ($results->isEmpty()) {
            return 0.0;
        }

        $totalCreditHours = 0;
        $totalGradePoints = 0;

        foreach ($results as $result) {
            $course = $result->course;
            $totalCreditHours += $course->credit_hours;
            $totalGradePoints += ($result->grade_point * $course->credit_hours);
        }

        return $totalCreditHours > 0 ? round($totalGradePoints / $totalCreditHours, 2) : 0.0;
    }
    
    /**
     * Calculate GPA for a specific semester or academic year
     *
     * @param int|null $semesterId
     * @param int|null $academicYearId
     * @return float
     */
    public function calculateGPA(?int $semesterId = null, ?int $academicYearId = null): float
    {
        $query = $this->results();
        
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        $results = $query->get();

        if ($results->isEmpty()) {
            return 0.0;
        }

        $totalCreditHours = 0;
        $totalGradePoints = 0;

        foreach ($results as $result) {
            $course = $result->course;
            $totalCreditHours += $course->credit_hours;
            $totalGradePoints += ($result->grade_point * $course->credit_hours);
        }

        return $totalCreditHours > 0 ? round($totalGradePoints / $totalCreditHours, 2) : 0.0;
    }

    /**
     * Get the student's classification based on CGPA.
     *
     * @return string|null
     */
    public function getClassification(): ?string
    {
        $cgpa = $this->calculateCGPA();
        $classification = Classification::where('min_cgpa', '<=', $cgpa)
            ->where('max_cgpa', '>=', $cgpa)
            ->first();
            
        return $classification ? $classification->name : null;
    }
}
