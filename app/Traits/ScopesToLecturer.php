<?php

namespace App\Traits;

use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopesToLecturer
{
    /**
     * Determine whether the authenticated user is restricted to their own assigned courses.
     */
    protected function isScopedLecturer(): bool
    {
        $user = Auth::user();

        return $user && $user->hasRole('Lecturer') && !$user->hasRole('Super Admin') && !$user->hasRole('Exams Officer');
    }

    /**
     * Get the course IDs the authenticated lecturer is allowed to manage.
     */
    protected function lecturerCourseIds(): array
    {
        return Course::where('lecturer_id', Auth::id())->pluck('id')->toArray();
    }

    /**
     * Scope a course query to only the authenticated lecturer's assigned courses,
     * unless the user has broader course access.
     */
    protected function scopeToLecturer(Builder $query): Builder
    {
        if ($this->isScopedLecturer()) {
            $query->where('lecturer_id', Auth::id());
        }

        return $query;
    }
}
