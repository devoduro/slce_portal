<?php

namespace App\Models;

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
        'full_name',
        'date_of_birth',
        'gender',
        'programme_id',
        'level',
        'class_group_id',
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
     * Get the total outstanding arrears across all previous years.
     * This is informational only and does not affect the course-registration fee gate.
     */
    public function totalArrears(): float
    {
        return (float) $this->arrears()->sum('amount');
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
     * Get the fee structure that applies to this student for a given academic year.
     * Matches on programme + level first, falling back to a programme-wide (null level) entry.
     */
    public function applicableFeeStructure(AcademicYear $academicYear): ?FeeStructure
    {
        $query = FeeStructure::where('academic_year_id', $academicYear->id)
            ->where('programme_id', $this->programme_id);

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
     * Get the student's outstanding fee balance for a given academic year.
     */
    public function feeBalance(AcademicYear $academicYear): float
    {
        $structure = $this->applicableFeeStructure($academicYear);

        if (!$structure) {
            return 0.0;
        }

        return max(0, (float) $structure->amount - $this->totalPaid($academicYear));
    }

    /**
     * Get the percentage of the applicable fee the student has paid for a given academic year.
     */
    public function paymentPercentage(AcademicYear $academicYear): float
    {
        $structure = $this->applicableFeeStructure($academicYear);

        if (!$structure || (float) $structure->amount <= 0) {
            return 0.0;
        }

        return round(($this->totalPaid($academicYear) / (float) $structure->amount) * 100, 2);
    }

    /**
     * Determine whether the student has paid enough of their fees to register courses
     * for the given semester.
     */
    public function meetsRegistrationThreshold(Semester $semester): bool
    {
        $required = (float) ($semester->required_payment_percentage ?? 0);

        if ($required <= 0) {
            return true;
        }

        return $this->paymentPercentage($semester->academicYear) >= $required;
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
