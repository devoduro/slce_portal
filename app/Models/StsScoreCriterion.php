<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * One line on the STS score sheet for a given level: the label supervisors see and the maximum
 * mark it carries. Defined by the STS coordinator rather than fixed in code, so the criteria can
 * differ level to level and be renamed without a release.
 */
class StsScoreCriterion extends Model
{
    use HasFactory;

    protected $table = 'sts_score_criteria';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'level',
        'label',
        'max_mark',
        'position',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
        'max_mark' => 'decimal:2',
        'position' => 'integer',
    ];

    /**
     * The marks supervisors have entered against this criterion.
     */
    public function scores(): HasMany
    {
        return $this->hasMany(StsPlacementScore::class, 'sts_score_criterion_id');
    }

    /**
     * The criteria that make up a level's score sheet, in the order the admin arranged them.
     *
     * @return Collection<int, StsScoreCriterion>
     */
    public static function forLevel(?int $level): Collection
    {
        if ($level === null) {
            return collect();
        }

        return static::where('level', $level)->orderBy('position')->orderBy('id')->get();
    }

    /**
     * Total marks a level's score sheet is out of.
     */
    public static function totalForLevel(?int $level): float
    {
        return (float) static::forLevel($level)->sum('max_mark');
    }
}
