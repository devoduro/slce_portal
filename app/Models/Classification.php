<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'min_cgpa',
        'max_cgpa',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_cgpa' => 'decimal:2',
        'max_cgpa' => 'decimal:2',
    ];
    
    /**
     * Get the classification for a given CGPA.
     *
     * @param float $cgpa
     * @return string|null
     */
    public static function getClassificationForCGPA(float $cgpa): ?string
    {
        $classification = self::where('min_cgpa', '<=', $cgpa)
            ->where('max_cgpa', '>=', $cgpa)
            ->first();
            
        return $classification ? $classification->name : null;
    }
}
