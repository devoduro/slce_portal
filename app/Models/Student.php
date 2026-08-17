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
     * Get the class group the student is assigned to.
     */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
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
     * Get the total outstanding arrears carried in from all previous years - i.e. the
     * ledger's overall running balance minus the current academic year's own balance, so it
     * reflects the closing balance of the previous year(s) rather than a stale/manually-entered
     * arrear figure that never gets updated when a year rolls over without a payment shortfall
     * being converted into a new arrear row.
     * This is informational only and does not affect the course-registration fee gate.
     */
    public function totalArrears(): float
    {
        $balanceDue = FeeLedgerService::ledgerFor($this)[0]['balance'] ?? 0.0;

        $currentYear = AcademicYear::where('is_current', true)->first();
        $currentYearBalance = $currentYear ? $this->feeBalance($currentYear) : 0.0;

        return $balanceDue - $currentYearBalance;
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
     * Get the fee structure that applies to this student for a given academic year and
     * category (defaults to the base tuition/school fee - the one that gates course
     * registration). Matches on programme + level first, falling back to a
     * programme-wide (null level) entry.
     */
    public function applicableFeeStructure(AcademicYear $academicYear, string $category = 'tuition'): ?FeeStructure
    {
        $query = FeeStructure::where('academic_year_id', $academicYear->id)
            ->where('programme_id', $this->programme_id)
            ->where('category', $category);

        if ($this->level !== null) {
            $structure = (clone $query)->where('level', $this->level)->first();
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
     * Get the student's outstanding fee balance for a given academic year: Bill Amount + All
     * Arrears - Payments. Arrears are entered as a running carried-forward adjustment (positive
     * = still-owed debt, negative = credit/overpayment) rather than scoped to a specific year's
     * own activity, so every arrear row counts here regardless of which academic_year_id it
     * happens to be tagged with. Not floored at zero - a large enough credit correctly shows as
     * a negative balance here, same as the other balance figures on the account.
     */
    public function feeBalance(AcademicYear $academicYear): float
    {
        $feeAmount = $this->tuitionFeeAmount($academicYear);

        if ($feeAmount <= 0) {
            return 0.0;
        }

        $allArrears = (float) $this->arrears()->sum('amount');

        return $feeAmount + $allArrears - $this->totalPaid($academicYear);
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
        $results = $this->results->filter(fn ($result) => $result->counts_for_gpa);

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
        
        $results = $query->get()->filter(fn ($result) => $result->counts_for_gpa);

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
