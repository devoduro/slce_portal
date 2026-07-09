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
        'registration_start_date',
        'registration_end_date',
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
        'registration_start_date' => 'date',
        'registration_end_date' => 'date',
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

    /**
     * Whether course registration is actually open right now. The `registration_open`
     * toggle is the admin's master switch (must be on for registration to ever be
     * possible); if a registration date window is also set, registration is only open
     * within that window. Leaving both dates blank preserves pure manual on/off control.
     */
    public function isRegistrationOpen(): bool
    {
        if (!$this->registration_open) {
            return false;
        }

        if (!$this->registration_start_date && !$this->registration_end_date) {
            return true;
        }

        $today = now()->startOfDay();

        if ($this->registration_start_date && $today->lt($this->registration_start_date)) {
            return false;
        }

        if ($this->registration_end_date && $today->gt($this->registration_end_date)) {
            return false;
        }

        return true;
    }
}
