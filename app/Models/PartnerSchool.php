<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PartnerSchool extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'location',
        'category',
        'type',
        'sts_term_id',
        'capacity_level_100',
        'capacity_level_200',
        'capacity_level_300',
        'capacity_level_400',
        'total_capacity',
    ];

    public const CATEGORY_LABELS = [
        'early_grade' => 'Early Grade',
        'upper_primary' => 'Upper Primary',
        'jhs_le' => 'JHS - Languages',
        'jhs_he' => 'JHS - Home Economics',
    ];

    public const TYPE_LABELS = [
        'sts' => 'STS',
        'internship' => 'Internship',
    ];

    /**
     * Get the placements at this school.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(StsPlacement::class);
    }

    /**
     * The levels a school holds a quota for.
     */
    public const QUOTA_LEVELS = [100, 200, 300, 400];

    /**
     * The levels a school of a given type can actually take students at.
     *
     * STS runs Level 100 up to the term's cutoff, in a First Semester term. Internship starts at
     * the cutoff (Level 300, Second Semester) and continues one more semester at the level above
     * it. So an internship school is never asked for Level 100 or 200 places, and an STS school
     * is never asked for Level 400 - offering those boxes invites quotas that can never be filled
     * and makes a school look like it has room it cannot use.
     *
     * A school with no type set yet (legacy rows predating the field) is left unrestricted rather
     * than guessed at.
     *
     * @return array<int, int>
     */
    public static function levelsForType(?string $type, ?StsTerm $term = null): array
    {
        $cutoff = (int) ($term->internship_level_cutoff ?? 300);

        if ($type === 'sts') {
            return array_values(array_filter(self::QUOTA_LEVELS, fn (int $level) => $level <= $cutoff));
        }

        if ($type === 'internship') {
            return array_values(array_filter(self::QUOTA_LEVELS, fn (int $level) => $level >= $cutoff));
        }

        return self::QUOTA_LEVELS;
    }

    /**
     * The levels a term will actually place a school of this type's students at, from the same
     * rule that assigns placement types (StsPlacement::determineType). Narrower than
     * levelsForType() because it accounts for the term's semester - a First Semester term has no
     * Level 300 internships, a Second Semester term has no STS. Used when (re)writing a term's
     * quota rows so they only cover levels that can genuinely be filled.
     *
     * @return array<int, int>
     */
    public static function activeLevelsForTerm(?string $type, StsTerm $term): array
    {
        if ($type !== 'sts' && $type !== 'internship') {
            return self::QUOTA_LEVELS;
        }

        $semesterNumber = (int) ($term->semester->semester_number ?? 1);
        $cutoff = (int) $term->internship_level_cutoff;
        $semesterCutoff = (int) $term->internship_semester_cutoff;

        return array_values(array_filter(
            self::QUOTA_LEVELS,
            fn (int $level) => StsPlacement::determineType($level, $semesterNumber, $cutoff, $semesterCutoff) === $type
        ));
    }

    /**
     * Rewrite a school's per-term quota rows from its own capacity_level_* columns, for the term
     * that governs it right now (an STS school's own term; the current term for a global /
     * internship school). Called whenever those columns are edited so the figure that actually
     * gates placement follows the edit - without it the quota rows, once written, ignore every
     * later capacity change.
     *
     * A continuing internship cohort holds the capacity it was allocated a level below (this
     * term's Level 400 interns fill the school's Level 300 places), so that one level is mapped
     * through the same shift the carry-forward uses. Every other active level is taken straight,
     * and any level this term never fills is written as zero.
     */
    public function syncQuotaToCapacities(): void
    {
        $term = $this->type === 'sts'
            ? ($this->sts_term_id ? StsTerm::find($this->sts_term_id) : null)
            : StsTerm::where('is_current', true)->first();

        // No governing term, or a term still falling back to the legacy columns anyway - the
        // edit already reaches placement, nothing to write.
        if (!$term) {
            return;
        }

        $hasRows = $this->quotas()->where('sts_term_id', $term->id)->exists();

        if (!$hasRows) {
            return;
        }

        $active = self::activeLevelsForTerm($this->type, $term);
        $cutoff = (int) $term->internship_level_cutoff;

        foreach (self::QUOTA_LEVELS as $level) {
            if (!in_array($level, $active, true)) {
                $capacity = 0;
            } else {
                $continuing = $this->type === 'internship' && $level > $cutoff;
                $sourceLevel = $continuing ? $level - 100 : $level;
                $capacity = (int) ($this->{"capacity_level_{$sourceLevel}"} ?? 0);
            }

            PartnerSchoolQuota::updateOrCreate(
                ['partner_school_id' => $this->id, 'sts_term_id' => $term->id, 'level' => $level],
                ['capacity' => $capacity]
            );
        }
    }

    /**
     * Which placement types draw on a given level, for labelling a quota column.
     *
     * @return array<int, string>
     */
    public static function typesForLevel(int $level, ?StsTerm $term = null): array
    {
        $cutoff = (int) ($term->internship_level_cutoff ?? 300);

        $types = [];

        if ($level <= $cutoff) {
            $types[] = 'sts';
        }

        if ($level >= $cutoff) {
            $types[] = 'internship';
        }

        return $types;
    }

    /**
     * Whether this school can take students at a level at all, given its type.
     */
    public function acceptsLevel(int $level, ?StsTerm $term = null): bool
    {
        return in_array($level, self::levelsForType($this->type, $term), true);
    }

    /**
     * This school's per-term quota rows.
     */
    public function quotas(): HasMany
    {
        return $this->hasMany(PartnerSchoolQuota::class);
    }

    /**
     * Get the quota for a given level, for a given term.
     *
     * A term's own row wins when one exists; otherwise the school's original
     * capacity_level_100..400 columns stand in, so a school nobody has set term quotas for keeps
     * behaving exactly as it always has.
     *
     * Never inferred from a neighbouring level. A level's quota moving up with a promoted cohort
     * is a real edit against a real term (PartnerSchoolQuotaController::carryForward()), not a
     * guess made at read time - guessing would show the same physical slots as open at two
     * levels at once, which is how a nine-place school ends up advertising eighteen.
     */
    public function capacityForLevel(int $level, ?StsTerm $term = null): int
    {
        if ($term) {
            $rows = $this->relationLoaded('quotas')
                ? $this->quotas->filter(fn (PartnerSchoolQuota $q) => (int) $q->sts_term_id === (int) $term->id)
                : $this->quotas()->where('sts_term_id', $term->id)->get();

            // All-or-nothing per school per term: once a term states this school's allocation it
            // states all of it, and a level it does not mention is zero. Falling back level by
            // level would leave a level the term deliberately emptied - because its cohort moved
            // up - still advertising last year's places alongside the ones they moved into.
            if ($rows->isNotEmpty()) {
                return (int) ($rows->firstWhere('level', $level)->capacity ?? 0);
            }
        }

        return (int) ($this->{"capacity_level_{$level}"} ?? 0);
    }

    /**
     * Bulk quota lookup for a listing: school id => level => capacity, for one term. Falls back
     * per school/level to the legacy columns, so callers get one complete picture either way.
     *
     * @param  \Illuminate\Support\Collection<int, PartnerSchool>  $schools
     * @return array<int, array<int, int>>
     */
    public static function capacitiesFor(\Illuminate\Support\Collection $schools, ?StsTerm $term): array
    {
        $rows = $term
            ? PartnerSchoolQuota::whereIn('partner_school_id', $schools->pluck('id'))
                ->where('sts_term_id', $term->id)
                ->get()
                ->groupBy('partner_school_id')
            : collect();

        $capacities = [];

        foreach ($schools as $school) {
            $termRows = $rows->get($school->id, collect())->keyBy('level');
            $termDefines = $termRows->isNotEmpty();

            foreach (self::QUOTA_LEVELS as $level) {
                // Same all-or-nothing rule as capacityForLevel(): a term that states this
                // school's allocation states all of it.
                $capacities[$school->id][$level] = $termDefines
                    ? (int) ($termRows[$level]->capacity ?? 0)
                    : (int) ($school->{"capacity_level_{$level}"} ?? 0);
            }
        }

        return $capacities;
    }

    /**
     * Get the number of quota slots still open for a given level within a term.
     *
     * Not floored at zero: a school can genuinely be over its quota - a capacity edited down
     * after students were placed, or a carried-over cohort landing on a level whose quota was
     * never set - and flattening that to 0 is how it goes unnoticed. Callers deciding whether
     * one more student fits should test for > 0.
     */
    public function availableQuota(int $level, StsTerm|int $term): int
    {
        $term = $term instanceof StsTerm ? $term : StsTerm::find($term);

        if (!$term) {
            return 0;
        }

        $placed = (int) (self::placedCountsFor(collect([$this]), $term)
            ->get($this->id, collect())
            ->firstWhere('level', $level)->total ?? 0);

        return $this->capacityForLevel($level, $term) - $placed;
    }

    /**
     * SQL for the level a placement's slot is charged to: simply the level the student is at.
     *
     * A promoted batch's allocation moves up with them - last term's Level 300 quota is written
     * as this term's Level 400 quota (PartnerSchoolQuotaController::carryForward()) - so the
     * places and the students who fill them sit at the same level and no remapping is needed.
     * A term whose quotas have not been carried forward will show its continuing cohort as over
     * quota, which is the honest signal that the allocation still needs moving.
     *
     * The level is the one the student holds *now*, not the one stamped on the placement when it
     * was created: a promotion run after the term was activated leaves that column behind, so
     * counting it would book their slot at a level they have left. Past terms keep their stored
     * level, which is the record of what that term actually was.
     */
    protected static function quotaLevelExpression(StsTerm $term): string
    {
        return $term->is_current
            ? 'COALESCE(students.level, sts_placements.level)'
            : 'sts_placements.level';
    }

    /**
     * Bulk "how many of each level's slots are taken" for a set of schools, keyed by school id
     * then level, in one query - so a listing of hundreds of schools stays a single lookup.
     *
     * @param  \Illuminate\Support\Collection<int, PartnerSchool>  $schools
     * @return \Illuminate\Support\Collection
     */
    public static function placedCountsFor(\Illuminate\Support\Collection $schools, StsTerm $term)
    {
        $schoolIds = $schools->pluck('id')->filter();

        if ($schoolIds->isEmpty()) {
            return collect();
        }

        $level = self::quotaLevelExpression($term);
        $cutoff = (int) $term->internship_level_cutoff;
        $studentLevel = $term->is_current
            ? 'COALESCE(students.level, sts_placements.level)'
            : 'sts_placements.level';

        // Resolved per placement in a subquery, then grouped by the alias. Grouping on the CASE
        // expression directly trips MySQL's ONLY_FULL_GROUP_BY, which will not accept that the
        // columns inside it are covered by an identical expression in the GROUP BY.
        // `continuing` counts how many of a level's taken slots are students carrying on an
        // internship from a level below, so the listing can say why a level looks full.
        $rows = StsPlacement::query()
            ->where('sts_placements.sts_term_id', $term->id)
            ->whereIn('sts_placements.partner_school_id', $schoolIds)
            ->selectRaw(
                "sts_placements.partner_school_id, {$level} as level,"
                . " CASE WHEN {$studentLevel} > {$cutoff} THEN 1 ELSE 0 END as is_continuing"
            );

        if ($term->is_current) {
            $rows->leftJoin('students', 'students.id', '=', 'sts_placements.student_id');
        }

        return DB::query()
            ->fromSub($rows, 'placement_levels')
            ->selectRaw('partner_school_id, level, COUNT(*) as total, SUM(is_continuing) as continuing')
            ->groupBy('partner_school_id', 'level')
            ->get()
            ->groupBy('partner_school_id');
    }

    /**
     * How many of a level's slots at this school are taken in a term, counted the same way as
     * placedCountsFor() but as a single locked-safe query for the selection gate.
     */
    public static function placedAtLevel(int $schoolId, int $level, StsTerm $term): int
    {
        $expression = self::quotaLevelExpression($term);

        $query = StsPlacement::query()
            ->where('sts_placements.partner_school_id', $schoolId)
            ->where('sts_placements.sts_term_id', $term->id)
            ->whereRaw("{$expression} = ?", [$level]);

        if ($term->is_current) {
            $query->leftJoin('students', 'students.id', '=', 'sts_placements.student_id');
        }

        return $query->count();
    }

    public function categoryLabel(): ?string
    {
        return self::CATEGORY_LABELS[$this->category] ?? null;
    }

    public function typeLabel(): ?string
    {
        return self::TYPE_LABELS[$this->type] ?? null;
    }

    /**
     * The STS term this school was uploaded for. Null for internship schools, which are kept
     * global and carry over from year to year.
     */
    public function stsTerm(): BelongsTo
    {
        return $this->belongsTo(StsTerm::class, 'sts_term_id');
    }

    /**
     * Limit to schools usable in a given term: STS schools uploaded for that term, plus every
     * school that isn't term-scoped (internship, and any legacy row predating this scoping).
     */
    public function scopeUsableInTerm($query, ?int $stsTermId)
    {
        return $query->where(fn ($q) => $q->whereNull('sts_term_id')->orWhere('sts_term_id', $stsTermId));
    }
}
