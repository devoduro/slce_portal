<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonAttendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'timetable_entry_id',
        'student_id',
        'attendance_date',
        'biometric_log_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attendance_date' => 'date',
    ];

    /**
     * Get the timetable entry (lesson) this attendance record is for.
     */
    public function timetableEntry(): BelongsTo
    {
        return $this->belongsTo(TimetableEntry::class);
    }

    /**
     * Get the student who attended.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the biometric log that triggered this attendance record.
     */
    public function biometricLog(): BelongsTo
    {
        return $this->belongsTo(BiometricLog::class);
    }
}
