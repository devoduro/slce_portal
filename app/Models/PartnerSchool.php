<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'capacity_level_100',
        'capacity_level_200',
        'capacity_level_300',
        'capacity_level_400',
    ];

    public const CATEGORY_LABELS = [
        'early_grade' => 'Early Grade',
        'upper_primary' => 'Upper Primary',
        'jhs' => 'JHS',
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
}
