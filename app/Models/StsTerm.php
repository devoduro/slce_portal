<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StsTerm extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'semester_id',
        'name',
        'proposed_start_date',
        'proposed_end_date',
        'internship_level_cutoff',
        'internship_semester_cutoff',
        'is_current',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'proposed_start_date' => 'date',
        'proposed_end_date' => 'date',
        'is_current' => 'boolean',
    ];

    /**
     * Get the semester this STS term is linked to (inherits its fee threshold and academic year).
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * Get the student placements created under this term.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(StsPlacement::class);
    }
}
