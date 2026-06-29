<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'grade_scheme_id',
        'letter',
        'min_score',
        'gpa_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_score' => 'decimal:2',
        'gpa_value' => 'decimal:2',
    ];

    /**
     * Get the grade scheme that owns this grade.
     */
    public function gradeScheme(): BelongsTo
    {
        return $this->belongsTo(GradeScheme::class);
    }
}
