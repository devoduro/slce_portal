<?php

namespace App\Services;

use App\Models\CaScoreSetting;
use App\Models\Course;
use App\Models\LessonAttendance;
use App\Models\Semester;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceScoreCalculator
{
    /**
     * Calculate a student's live attendance score for a course in a given semester,
     * scaled against the attendance max marks configured for the student's level.
     */
    public static function score(Student $student, Course $course, Semester $semester): float
    {
        $setting = CaScoreSetting::where('level', $student->level)->first();

        if (!$setting) {
            return 0.0;
        }

        $entries = $course->timetableEntries()->where('semester_id', $semester->id)->get();

        if ($entries->isEmpty() || !$semester->start_date || !$semester->end_date) {
            return 0.0;
        }

        $cutoff = Carbon::now()->lessThan($semester->end_date) ? Carbon::now() : Carbon::parse($semester->end_date);

        if ($cutoff->lessThan($semester->start_date)) {
            return 0.0;
        }

        $expected = 0;
        foreach ($entries as $entry) {
            $expected += self::countWeekdayOccurrences(
                Carbon::parse($semester->start_date),
                $cutoff,
                (int) $entry->day_of_week
            );
        }

        if ($expected <= 0) {
            return 0.0;
        }

        $attended = LessonAttendance::where('student_id', $student->id)
            ->whereIn('timetable_entry_id', $entries->pluck('id'))
            ->count();

        return round(min(1, $attended / $expected) * (float) $setting->attendance_max, 2);
    }

    /**
     * Count how many times a given day of week (0 = Sunday .. 6 = Saturday) occurs
     * between two dates, inclusive.
     */
    protected static function countWeekdayOccurrences(Carbon $start, Carbon $end, int $dayOfWeek): int
    {
        if ($end->lessThan($start)) {
            return 0;
        }

        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->dayOfWeek === $dayOfWeek) {
                $count++;
            }
        }

        return $count;
    }
}
