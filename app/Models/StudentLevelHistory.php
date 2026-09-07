<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A snapshot of the level a student held during a specific academic year, taken at the moment
 * they're promoted out of it (see PromotionController). Lets fee lookups use the level a
 * student actually was at during a past year, instead of their current level.
 */
class StudentLevelHistory extends Model
{
    protected $table = 'student_level_history';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'level',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
