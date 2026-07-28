<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StsAttendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sts_placement_id',
        'student_id',
        'attendance_date',
        'biometric_log_id',
        'source',
        'recorded_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function stsPlacement(): BelongsTo
    {
        return $this->belongsTo(StsPlacement::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function biometricLog(): BelongsTo
    {
        return $this->belongsTo(BiometricLog::class);
    }

    /**
     * Get the user who manually recorded this attendance, if it wasn't biometric-derived.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
