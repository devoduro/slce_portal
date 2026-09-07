<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A supervisor's mark for one student against one criterion of the STS score sheet.
 */
class StsPlacementScore extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sts_placement_id',
        'sts_score_criterion_id',
        'score',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function placement(): BelongsTo
    {
        return $this->belongsTo(StsPlacement::class, 'sts_placement_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(StsScoreCriterion::class, 'sts_score_criterion_id');
    }
}
