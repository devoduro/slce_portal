<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeScheme extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_default',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];
    
    /**
     * Get the grades for this scheme.
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class)->orderBy('min_score', 'desc');
    }
    
    /**
     * Get the default grade scheme.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }
    
    /**
     * Get the grade for a given score.
     */
    public function getGradeForScore(float $score): ?string
    {
        $grade = $this->grades()
            ->where('min_score', '<=', $score)
            ->orderBy('min_score', 'desc')
            ->first();
            
        return $grade ? $grade->grade : null;
    }
    
    /**
     * Get the grade point for a given score.
     */
    public function getGradePointForScore(float $score): ?float
    {
        $grade = $this->grades()
            ->where('min_score', '<=', $score)
            ->orderBy('min_score', 'desc')
            ->first();
            
        return $grade ? $grade->gpa_value : null;
    }
    
    /**
     * Static methods for compatibility with existing code
     */
    public static function getGrade(float $score): ?string
    {
        $defaultScheme = self::getDefault();
        return $defaultScheme ? $defaultScheme->getGradeForScore($score) : null;
    }
    
    public static function getGradePoint(float $score): ?float
    {
        $defaultScheme = self::getDefault();
        return $defaultScheme ? $defaultScheme->getGradePointForScore($score) : null;
    }
    
    public static function getRemark(float $score): ?string
    {
        // For now, just return a simple remark based on score
        if ($score >= 70) return 'Excellent';
        if ($score >= 60) return 'Very Good';
        if ($score >= 50) return 'Good';
        if ($score >= 40) return 'Fair';
        return 'Poor';
    }
    
    /**
     * Get the score for a given letter grade.
     */
    public function getScoreFromGrade(string $grade): ?float
    {
        $gradeRecord = $this->grades()
            ->where('grade', strtoupper($grade))
            ->first();
            
        return $gradeRecord ? $gradeRecord->min_score : null;
    }
}
