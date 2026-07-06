<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lecturer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'staff_id',
        'department_id',
        'profile_photo',
    ];

    /**
     * Get the department this lecturer belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the initials for this lecturer's name (e.g. "John Kwame Mensah" -> "JKM"),
     * used on the compact timetable grid where full names don't fit.
     */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        $letters = array_map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)), array_filter($words));

        return implode('', array_slice($letters, 0, 3));
    }

    /**
     * Get the portal user account linked to this lecturer, if any.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get the courses this lecturer is assigned to teach.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Get the timetable entries this lecturer is assigned to.
     */
    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }
}
