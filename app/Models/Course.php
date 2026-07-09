<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'title',
        'description',
        'credit_hours',
        'semester_id',
        'is_core',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credit_hours' => 'decimal:2',
        'is_core' => 'boolean',
    ];
    
    /**
     * Get the programmes associated with the course.
     */
    public function programmes(): BelongsToMany
    {
        return $this->belongsToMany(Programme::class);
    }
    
    /**
     * Get the semester that owns the course.
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
    
    /**
     * Get the results for the course.
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Get the lecturers assigned to teach this course. A course can have more than one
     * lecturer (e.g. different lecturers teaching different sections of the same course).
     */
    public function lecturers(): BelongsToMany
    {
        return $this->belongsToMany(Lecturer::class, 'course_lecturer');
    }

    /**
     * Get the timetable entries (scheduled lesson slots) for this course.
     */
    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }

    /**
     * Get the prerequisite course for this course.
     */
    public function prerequisite()
    {
        return $this->belongsTo(Course::class, 'prerequisite_id');
    }

    /**
     * Get the courses that have this course as their prerequisite.
     */
    public function dependentCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'prerequisite_id');
    }
}
