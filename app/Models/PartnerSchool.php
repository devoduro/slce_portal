<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Get the configured quota for a given level.
     */
    public function capacityForLevel(int $level): int
    {
        return (int) ($this->{"capacity_level_{$level}"} ?? 0);
    }

    /**
     * Get the number of quota slots still open for a given level within a term.
     */
    public function availableQuota(int $level, int $stsTermId): int
    {
        $filled = $this->placements()
            ->where('sts_term_id', $stsTermId)
            ->where('level', $level)
            ->count();

        return max(0, $this->capacityForLevel($level) - $filled);
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
