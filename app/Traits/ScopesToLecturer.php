<?php

namespace App\Traits;

use App\Models\Course;
use App\Models\Registration;
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

        return Course::whereHas('lecturers', fn ($q) => $q->where('lecturers.id', $lecturerId))
            ->pluck('id')
            ->toArray();
    }

    /**
     * Scope a course query to only courses the authenticated lecturer is assigned to
     * (one of possibly several lecturers on that course), unless the user has broader
     * course access.
     */
    protected function scopeToLecturer(Builder $query): Builder
    {
        if ($this->isScopedLecturer()) {
            // No linked lecturer profile yet -> show nothing rather than everything.
            $query->whereHas('lecturers', fn ($q) => $q->where('lecturers.id', $this->authLecturerId() ?? 0));
        }

        return $query;
    }

    /**
     * Get the distinct IDs of students registered in the authenticated lecturer's
     * courses (i.e. "the students they teach"), for scoping student pickers/filters.
     */
    protected function lecturerStudentIds(): array
    {
        $courseIds = $this->lecturerCourseIds();

        if (empty($courseIds)) {
            return [];
        }

        return Registration::whereIn('course_id', $courseIds)
            ->where('status', 'registered')
            ->distinct()
            ->pluck('student_id')
            ->toArray();
    }
}
