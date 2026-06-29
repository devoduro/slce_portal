<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];
    
    /**
     * Get the semesters for the academic year.
     */
    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }
    
    /**
     * Get the results for the academic year.
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }
    
    /**
     * Get the start year attribute.
     *
     * @return int
     */
    public function getStartYearAttribute()
    {
        return (int) explode('/', $this->name)[0];
    }
    
    /**
     * Get the end year attribute.
     *
     * @return int
     */
    public function getEndYearAttribute()
    {
        return (int) explode('/', $this->name)[1];
    }
    
    /**
     * Scope a query to get academic years in chronological order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    /**
     * Get the first year from the academic year name (e.g., 2024 from '2024/2025')
     */
    public function getFirstYearAttribute()
    {
        return (int) explode('/', $this->name)[0];
    }

    /**
     * Get the second year from the academic year name (e.g., 2025 from '2024/2025')
     */
    public function getSecondYearAttribute()
    {
        return (int) explode('/', $this->name)[1];
    }

    /**
     * Scope a query to order academic years chronologically (newest first)
     */
    public function scopeChronological($query)
    {
        return $query->orderByRaw('CAST(SUBSTRING_INDEX(name, "/", 1) AS UNSIGNED) DESC');
    }
}
