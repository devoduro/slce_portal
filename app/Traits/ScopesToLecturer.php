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
     * Get the lecturer profile ID linked to the authenticated user, if any.
     */
    protected function authLecturerId(): ?int
    {
        return Auth::user()?->lecturer_id;
    }

    /**
     * Get the course IDs the authenticated lecturer is allowed to manage.
     */
    protected function lecturerCourseIds(): array
    {
        $lecturerId = $this->authLecturerId();

        if (!$lecturerId) {
            return [];
        }

        return Course::where('lecturer_id', $lecturerId)->pluck('id')->toArray();
    }

    /**
     * Scope a course query to only the authenticated lecturer's assigned courses,
     * unless the user has broader course access.
     */
    protected function scopeToLecturer(Builder $query): Builder
    {
        if ($this->isScopedLecturer()) {
            // No linked lecturer profile yet -> show nothing rather than everything.
            $query->where('lecturer_id', $this->authLecturerId() ?? 0);
        }

        return $query;
    }
}
