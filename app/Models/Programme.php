<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Programme extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'duration_years',
        'department',
        'faculty',
        'sts_category',
    ];

    public const STS_CATEGORY_LABELS = [
        'early_grade' => 'Early Grade',
        'upper_primary' => 'Upper Primary',
        'jhs_le' => 'JHS - Languages',
        'jhs_he' => 'JHS - Home Economics',
    ];

    /**
     * Human-readable label for this programme's STS/Internship category.
     */
    public function stsCategoryLabel(): ?string
    {
        return self::STS_CATEGORY_LABELS[$this->sts_category] ?? null;
    }
    
    /**
     * Get the students for the programme.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
    
    /**
     * Get the courses for the programme.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    /**
     * The final level a student on this programme reaches before graduating
     * (e.g. a 4-year programme's terminal level is 400).
     */
    public function terminalLevel(): int
    {
        return 100 + (max(1, $this->duration_years) - 1) * 100;
    }
}
