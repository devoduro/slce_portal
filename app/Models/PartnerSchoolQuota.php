<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one partner school agreed to take at one level, for one STS term.
 *
 * Scoping the allocation to a term is what lets it follow a cohort: when the batch placed at
 * Level 300 last term is promoted, this term's row for them is Level 400, and last term's row
 * stays as the record of what actually happened then.
 */
class PartnerSchoolQuota extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'partner_school_id',
        'sts_term_id',
        'level',
        'capacity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
        'capacity' => 'integer',
    ];

    public function partnerSchool(): BelongsTo
    {
        return $this->belongsTo(PartnerSchool::class);
    }

    public function stsTerm(): BelongsTo
    {
        return $this->belongsTo(StsTerm::class, 'sts_term_id');
    }
}
