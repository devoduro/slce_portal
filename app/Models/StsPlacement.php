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
     * The supervisor's marks against each criterion of this level's score sheet.
     */
    public function scores(): HasMany
    {
        return $this->hasMany(StsPlacementScore::class, 'sts_placement_id');
    }

    /**
     * Total marks awarded so far and what the sheet is out of, for this placement's level.
     *
     * @return array{awarded: float, total: float, scored: int, criteria: int}
     */
    public function scoreSummary(): array
    {
        $criteria = StsScoreCriterion::forLevel((int) $this->level);
        $scores = $this->scores->keyBy('sts_score_criterion_id');

        $awarded = 0.0;
        $scored = 0;

        foreach ($criteria as $criterion) {
            $score = $scores->get($criterion->id)?->score;

            if ($score !== null) {
                $awarded += (float) $score;
                $scored++;
            }
        }

        return [
            'awarded' => round($awarded, 2),
            'total' => round((float) $criteria->sum('max_mark'), 2),
            'scored' => $scored,
            'criteria' => $criteria->count(),
        ];
    }

    /**
     * Determine whether a level/semester combination falls under "STS" or "Internship", given
     * a term's configured cutoff, or null if the combination is outside the placement system
     * entirely.
     *
     * At exactly $levelCutoff (e.g. 300), it becomes Internship once the term's own semester
     * number reaches $semesterCutoff (e.g. 300/2 means "Level 300 Second Semester is Internship;
     * Level 300 First Semester is STS"). Above $levelCutoff (e.g. 400), only the first semester
     * continues as Internship - scored against the same placement/school/supervisor carried
     * over from the student's Level 300 internship term (see StsTermController::activate()) -
     * every semester after that is beyond this placement system (null).
     *
     * STS itself only ever runs in a First Semester term, and only up to $levelCutoff. A level
     * at or below the cutoff in any later semester falls outside the placement system rather
     * than repeating STS, and no student above the cutoff is given STS at all.
     */
    public static function determineType(int $level, int $termSemesterNumber, int $levelCutoff = 300, int $semesterCutoff = 2): ?string
    {
        if ($level > $levelCutoff) {
            return $termSemesterNumber === 1 ? self::TYPE_INTERNSHIP : null;
        }

        if ($level === $levelCutoff && $termSemesterNumber >= $semesterCutoff) {
            return self::TYPE_INTERNSHIP;
        }

        return $termSemesterNumber === 1 ? self::TYPE_STS : null;
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
