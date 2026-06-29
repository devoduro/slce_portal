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
