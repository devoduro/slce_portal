<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'academic_year_id',
        'name',
        'semester_number',
        'start_date',
        'end_date',
        'is_current',
        'required_payment_percentage',
        'registration_open',
        'biometric_window_open',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'required_payment_percentage' => 'decimal:2',
        'registration_open' => 'boolean',
        'biometric_window_open' => 'boolean',
    ];
    
    /**
     * Get the academic year that owns the semester.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
    
    /**
     * Get the courses for the semester.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
    
    /**
     * Get the results for the semester.
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Get the course registrations for the semester.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get the biometric registrations recorded for the semester.
     */
    public function biometricRegistrations(): HasMany
    {
        return $this->hasMany(BiometricRegistration::class);
    }
}
