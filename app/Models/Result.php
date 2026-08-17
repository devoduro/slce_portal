<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'course_id',
        'semester_id',
        'academic_year_id',
        'grade',
        'grade_point',
        'score',
        'remark',
        'is_repeated',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'grade_point' => 'decimal:2',
        'score' => 'decimal:2',
        'is_repeated' => 'boolean',
    ];
    
    /**
     * Get the student that owns the result.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    
    /**
     * Get the course that owns the result.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    /**
     * Get the semester that owns the result.
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
    
    /**
     * Get the academic year that owns the result.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Whether this row should be summed into GPA/CGPA for its student/course/semester/year.
     *
     * A resit row (is_repeated = true) always counts - it's the latest attempt. An
     * original row only counts if no resit sibling exists yet, so a resit supersedes
     * the original for GPA purposes while both rows stay on the transcript.
     */
    public function getCountsForGpaAttribute(): bool
    {
        if ($this->is_repeated) {
            return true;
        }

        return !self::where('student_id', $this->student_id)
            ->where('course_id', $this->course_id)
            ->where('semester_id', $this->semester_id)
            ->where('academic_year_id', $this->academic_year_id)
            ->where('is_repeated', true)
            ->exists();
    }
}
