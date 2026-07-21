<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StsPlacement extends Model
{
    use HasFactory;

    public const TYPE_STS = 'sts';
    public const TYPE_INTERNSHIP = 'internship';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'sts_term_id',
        'partner_school_id',
        'lecturer_id',
        'second_lecturer_id',
        'level',
        'type',
        'selected_at',
        'supervisor_assigned_at',
        'letter_printed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'selected_at' => 'datetime',
        'supervisor_assigned_at' => 'datetime',
        'letter_printed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function stsTerm(): BelongsTo
    {
        return $this->belongsTo(StsTerm::class);
    }

    public function partnerSchool(): BelongsTo
    {
        return $this->belongsTo(PartnerSchool::class);
    }

    /**
     * Get the assigned (primary) supervisor (a Lecturer).
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    /**
     * Get the second/co-supervisor (a Lecturer), if one is assigned.
     */
    public function secondLecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'second_lecturer_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StsAttendance::class);
    }

    /**
     * Determine whether a level/semester combination falls under "STS" or "Internship", given
     * a term's configured cutoff. Any level above $levelCutoff is always Internship; at exactly
     * $levelCutoff, it only becomes Internship once the term's own semester number reaches
     * $semesterCutoff (e.g. levelCutoff=300, semesterCutoff=2 means "Level 300 Second Semester
     * onward - and every level above 300 - is Internship; Level 300 First Semester is STS").
     */
    public static function determineType(int $level, int $termSemesterNumber, int $levelCutoff = 300, int $semesterCutoff = 2): string
    {
        if ($level > $levelCutoff) {
            return self::TYPE_INTERNSHIP;
        }

        if ($level === $levelCutoff && $termSemesterNumber >= $semesterCutoff) {
            return self::TYPE_INTERNSHIP;
        }

        return self::TYPE_STS;
    }

    /**
     * Resolve the synthetic Course row (STS or INTERNSHIP) this placement's scores write into.
     */
    public function course(): Course
    {
        $code = $this->type === self::TYPE_INTERNSHIP ? 'INTERNSHIP' : 'STS';

        return Course::where('code', $code)->where('is_sts_course', true)->firstOrFail();
    }

    /**
     * Human-readable placement progress for admin/student display.
     */
    public function statusLabel(): string
    {
        if ($this->partner_school_id && $this->lecturer_id) {
            return 'Ready';
        }

        if ($this->partner_school_id) {
            return 'School Selected';
        }

        return 'Pending Selection';
    }
}
