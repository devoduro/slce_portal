<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StsPlacement;
use App\Models\StsScoreSetting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class StsAttendanceScoreCalculator
{
    /**
     * Calculate a student's live mentor/attendance score for a placement, scaled against
     * the attendance max marks configured for the placement's level. Unlike regular lesson
     * attendance, STS attendance is a plain date-range (the STS term window), not a
     * recurring weekly lesson schedule.
     */
    public static function score(StsPlacement $placement): float
    {
        $setting = StsScoreSetting::where('level', $placement->level)->first();
        $term = $placement->stsTerm;

        if (!$setting || !$term || !$term->proposed_start_date || !$term->proposed_end_date) {
            return 0.0;
        }

        $cutoff = Carbon::now()->lessThan($term->proposed_end_date) ? Carbon::now() : Carbon::parse($term->proposed_end_date);

        if ($cutoff->lessThan($term->proposed_start_date)) {
            return 0.0;
        }

        $expected = self::countWeekdays(Carbon::parse($term->proposed_start_date), $cutoff);

        if ($expected <= 0) {
            return 0.0;
        }

        $attended = $placement->attendances()->count();

        return round(min(1, $attended / $expected) * (float) $setting->attendance_max, 2);
    }

    /**
     * Entry point called from AttendanceScoreCalculator::score() when the course is a
     * synthetic STS/Internship course - resolves the matching placement and delegates.
     */
    public static function scoreForCourse(Student $student, Semester $semester): float
    {
        $placement = StsPlacement::where('student_id', $student->id)
            ->whereHas('stsTerm', fn ($q) => $q->where('semester_id', $semester->id))
            ->first();

        return $placement ? self::score($placement) : 0.0;
    }

    /**
     * Count weekdays (Mon-Fri) between two dates, inclusive.
     */
    protected static function countWeekdays(Carbon $start, Carbon $end): int
    {
        if ($end->lessThan($start)) {
            return 0;
        }

        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if (!$date->isWeekend()) {
                $count++;
            }
        }

        return $count;
    }
}
