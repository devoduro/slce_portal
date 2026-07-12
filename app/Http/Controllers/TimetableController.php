<?php

namespace App\Http\Controllers;

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Semester;
use App\Models\TimetableEntry;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TimetableController extends Controller
{
    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    /**
     * Day order used to lay out the weekly grid (Monday first, Sunday last).
     */
    public const GRID_DAY_ORDER = [1, 2, 3, 4, 5, 6, 0];

    /**
     * Grid time axis: the visible window and slot resolution.
     */
    public const GRID_START_HOUR = 7;
    public const GRID_END_HOUR = 21;
    public const GRID_SLOT_MINUTES = 30;

    /**
     * Virtual/online lessons are only allowed on weekends or from this hour onward
     * (i.e. in the evening), so they don't collide with the normal daytime timetable.
     */
    public const EVENING_START_HOUR = 17;

    /**
     * Colours cycled through per course so each course is easy to tell apart at a glance.
     * Each entry pairs a solid background (grid cards) with a matching light background + text
     * (legend chips).
     */
    public const COLOR_PALETTE = [
        ['bg' => 'bg-blue-500', 'chip' => 'bg-blue-100 text-blue-800'],
        ['bg' => 'bg-emerald-500', 'chip' => 'bg-emerald-100 text-emerald-800'],
        ['bg' => 'bg-purple-500', 'chip' => 'bg-purple-100 text-purple-800'],
        ['bg' => 'bg-amber-500', 'chip' => 'bg-amber-100 text-amber-800'],
        ['bg' => 'bg-rose-500', 'chip' => 'bg-rose-100 text-rose-800'],
        ['bg' => 'bg-cyan-500', 'chip' => 'bg-cyan-100 text-cyan-800'],
        ['bg' => 'bg-indigo-500', 'chip' => 'bg-indigo-100 text-indigo-800'],
        ['bg' => 'bg-orange-500', 'chip' => 'bg-orange-100 text-orange-800'],
        ['bg' => 'bg-teal-500', 'chip' => 'bg-teal-100 text-teal-800'],
        ['bg' => 'bg-pink-500', 'chip' => 'bg-pink-100 text-pink-800'],
        ['bg' => 'bg-lime-600', 'chip' => 'bg-lime-100 text-lime-800'],
        ['bg' => 'bg-sky-500', 'chip' => 'bg-sky-100 text-sky-800'],
    ];

    /**
     * Get the colour pair assigned to a course (stable for a given course ID).
     */
    public static function colorForCourse(?int $courseId): array
    {
        if (!$courseId) {
            return ['bg' => 'bg-gray-400', 'chip' => 'bg-gray-100 text-gray-800'];
        }

        return self::COLOR_PALETTE[$courseId % count(self::COLOR_PALETTE)];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $classGroups = ClassGroup::orderBy('name')->get();
        $lecturers = Lecturer::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $semesters = Semester::orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();

        $filters = $request->only(['class_group_id', 'lecturer_id', 'department_id', 'semester_id', 'level']);

        $showGrid = $request->filled('semester_id')
            && ($request->filled('class_group_id') || $request->filled('lecturer_id') || $request->filled('department_id') || $request->filled('level'));

        $entries = collect();
        $paginatedEntries = null;
        $slotLabels = [];
        $workload = null;
        $classSummary = null;

        if ($showGrid) {
            $entries = self::fetchEntries($filters);
            self::applyGridPositions($entries);
            $slotLabels = self::gridSlotLabels();

            if (!empty($filters['lecturer_id'])) {
                $workload = self::calculateWorkload((int) $filters['lecturer_id'], (int) $filters['semester_id']);
            }

            if (!empty($filters['class_group_id'])) {
                $classSummary = self::classSummary((int) $filters['class_group_id'], $filters['semester_id'] ?? null);
            }
        } else {
            $paginatedEntries = self::fetchEntriesQuery($filters)
                ->paginate(20)->withQueryString();
        }

        return view('timetable.index', compact(
            'entries', 'paginatedEntries', 'classGroups', 'lecturers', 'departments', 'semesters', 'showGrid', 'slotLabels', 'workload', 'classSummary'
        ));
    }

    /**
     * Print-friendly timetable for a specific class and/or lecturer.
     */
    public function print(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'semester_id' => 'required|exists:semesters,id',
            'class_group_id' => 'nullable|exists:class_groups,id',
            'lecturer_id' => 'nullable|exists:lecturers,id',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|integer',
        ]);

        $hasScope = $request->filled('class_group_id') || $request->filled('lecturer_id') || $request->filled('department_id') || $request->filled('level');

        if ($validator->fails() || !$hasScope) {
            return redirect()->route('timetable.index')
                ->with('error', 'Select a class, lecturer, department or level, and a semester, before printing.');
        }

        return self::buildPrintView($request->only(['class_group_id', 'lecturer_id', 'department_id', 'semester_id', 'level']));
    }

    /**
     * Build the print-friendly timetable view for a given filter set. Public/static so the
     * lecturer and student self-service portals can print their own timetable without needing
     * the admin-only manage-timetable permission.
     *
     * @param \App\Models\Student|null $student Passed only when a student is printing their own
     *                                          timetable, so the letterhead can show their photo/index number.
     */
    public static function buildPrintView(array $filters, $student = null)
    {
        $semester = Semester::with('academicYear')->findOrFail($filters['semester_id']);
        $classGroup = !empty($filters['class_group_id']) ? ClassGroup::find($filters['class_group_id']) : null;
        $lecturer = !empty($filters['lecturer_id']) ? Lecturer::find($filters['lecturer_id']) : null;
        $department = !empty($filters['department_id']) ? Department::find($filters['department_id']) : null;
        $level = $filters['level'] ?? null;

        $entries = self::fetchEntries($filters);
        self::applyGridPositions($entries);
        $slotLabels = self::gridSlotLabels();

        $workload = $lecturer ? self::calculateWorkload($lecturer->id, (int) $filters['semester_id']) : null;
        $classSummary = $classGroup ? self::classSummary($classGroup->id, (int) $filters['semester_id']) : null;

        $settings = DB::table('settings')->where('category', 'institution')->pluck('value', 'key')->toArray();

        return view('timetable.print', compact('entries', 'slotLabels', 'semester', 'classGroup', 'lecturer', 'department', 'level', 'settings', 'workload', 'classSummary', 'student'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $classGroups = ClassGroup::orderBy('name')->get();
        $courses = Course::with('lecturers')->orderBy('code')->get();
        $lecturers = Lecturer::orderBy('name')->get();
        $venues = Venue::orderBy('name')->get();
        $semesters = Semester::orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();

        return view('timetable.create', compact('classGroups', 'courses', 'lecturers', 'venues', 'semesters'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());
        $this->applyVirtualScheduleCheck($validator, $request);

        if ($validator->fails()) {
            return redirect()->route('timetable.create')
                ->withErrors($validator)
                ->withInput();
        }

        $conflict = $this->findConflict($request);
        if ($conflict) {
            return redirect()->route('timetable.create')
                ->withErrors(['start_time' => $conflict])
                ->withInput();
        }

        $data = $request->only([
            'class_group_id', 'course_id', 'lecturer_id', 'venue_id', 'semester_id', 'day_of_week', 'start_time', 'end_time',
        ]);
        $data['is_virtual'] = $request->boolean('is_virtual');

        TimetableEntry::create($data);

        return redirect()->route('timetable.index')
            ->with('success', 'Timetable entry created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimetableEntry $timetable)
    {
        $classGroups = ClassGroup::orderBy('name')->get();
        $courses = Course::with('lecturers')->orderBy('code')->get();
        $lecturers = Lecturer::orderBy('name')->get();
        $venues = Venue::orderBy('name')->get();
        $semesters = Semester::orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();

        return view('timetable.edit', [
            'entry' => $timetable,
            'classGroups' => $classGroups,
            'courses' => $courses,
            'lecturers' => $lecturers,
            'venues' => $venues,
            'semesters' => $semesters,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TimetableEntry $timetable)
    {
        $validator = Validator::make($request->all(), $this->rules());
        $this->applyVirtualScheduleCheck($validator, $request);

        if ($validator->fails()) {
            return redirect()->route('timetable.edit', $timetable)
                ->withErrors($validator)
                ->withInput();
        }

        $conflict = $this->findConflict($request, $timetable->id);
        if ($conflict) {
            return redirect()->route('timetable.edit', $timetable)
                ->withErrors(['start_time' => $conflict])
                ->withInput();
        }

        $data = $request->only([
            'class_group_id', 'course_id', 'lecturer_id', 'venue_id', 'semester_id', 'day_of_week', 'start_time', 'end_time',
        ]);
        $data['is_virtual'] = $request->boolean('is_virtual');

        $timetable->update($data);

        return redirect()->route('timetable.index')
            ->with('success', 'Timetable entry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimetableEntry $timetable)
    {
        $timetable->delete();

        return redirect()->route('timetable.index')
            ->with('success', 'Timetable entry deleted successfully.');
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function rules(): array
    {
        return [
            'class_group_id' => 'required|exists:class_groups,id',
            'course_id' => 'required|exists:courses,id',
            'lecturer_id' => 'nullable|exists:lecturers,id',
            'venue_id' => 'nullable|required_unless:is_virtual,1|exists:venues,id',
            'semester_id' => 'required|exists:semesters,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_virtual' => 'nullable|boolean',
        ];
    }

    /**
     * Virtual/online lessons must sit outside the normal daytime schedule: weekends
     * are fine any time, weekdays only from EVENING_START_HOUR onward.
     */
    protected function applyVirtualScheduleCheck($validator, Request $request): void
    {
        $validator->after(function ($validator) use ($request) {
            if (!$request->boolean('is_virtual') || !$request->filled('day_of_week') || !$request->filled('start_time')) {
                return;
            }

            if (!self::isEveningOrWeekend((int) $request->day_of_week, (string) $request->start_time)) {
                $validator->errors()->add(
                    'is_virtual',
                    'Virtual/online classes must be scheduled on a weekend (Saturday or Sunday) or in the evening (5:00 PM or later).'
                );
            }
        });
    }

    /**
     * True if the given day/start-time falls on a weekend, or on a weekday at/after
     * EVENING_START_HOUR.
     */
    public static function isEveningOrWeekend(int $dayOfWeek, string $startTime): bool
    {
        if (in_array($dayOfWeek, [0, 6], true)) {
            return true;
        }

        return self::minutesSinceMidnight($startTime) >= self::EVENING_START_HOUR * 60;
    }

    /**
     * Check for a scheduling conflict: the same class already has a lesson at an
     * overlapping time on this day, or the same venue is already booked for an
     * overlapping time on this day.
     */
    protected function findConflict(Request $request, ?int $excludeId = null): ?string
    {
        $overlap = function ($query) use ($request, $excludeId) {
            $query->where('semester_id', $request->semester_id)
                ->where('day_of_week', $request->day_of_week)
                ->where('start_time', '<', $request->end_time)
                ->where('end_time', '>', $request->start_time);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        };

        $classClash = TimetableEntry::where('class_group_id', $request->class_group_id)
            ->where($overlap)
            ->exists();

        if ($classClash) {
            return 'This class already has a lesson scheduled at an overlapping time on this day.';
        }

        // A venue's admin-configured capacity decides how many simultaneous classes it may
        // legitimately host (e.g. a shared/split hall) — only reject once that many bookings
        // already exist. Virtual/online lessons with no physical venue skip this check entirely.
        if ($request->filled('venue_id')) {
            $venue = Venue::find($request->venue_id);
            $maxConcurrent = $venue?->max_concurrent_classes ?? 1;

            $venueBookingCount = TimetableEntry::where('venue_id', $request->venue_id)
                ->where($overlap)
                ->count();

            if ($venueBookingCount >= $maxConcurrent) {
                return $maxConcurrent > 1
                    ? "This venue already has {$maxConcurrent} classes booked at an overlapping time on this day."
                    : 'This venue is already booked at an overlapping time on this day.';
            }
        }

        return null;
    }

    /**
     * Build the filtered timetable entries query shared by the grid, flat-list and
     * print views, filtered by any combination of class group, lecturer, department
     * (via the lecturer's department) and semester.
     *
     * Public/static so the lecturer and student self-service portals can reuse the
     * exact same grid-building logic without duplicating it.
     */
    public static function fetchEntriesQuery(array $filters)
    {
        return TimetableEntry::with(['classGroup', 'course', 'lecturer.department', 'venue', 'semester'])
            ->when(!empty($filters['class_group_id']), fn ($q) => $q->where('class_group_id', $filters['class_group_id']))
            ->when(!empty($filters['lecturer_id']), fn ($q) => $q->where('lecturer_id', $filters['lecturer_id']))
            ->when(!empty($filters['department_id']), fn ($q) => $q->whereHas('lecturer', fn ($lq) => $lq->where('department_id', $filters['department_id'])))
            ->when(!empty($filters['semester_id']), fn ($q) => $q->where('semester_id', $filters['semester_id']))
            ->when(!empty($filters['level']), fn ($q) => $q->whereHas('classGroup', fn ($cq) => $cq->where('level', $filters['level'])))
            ->orderBy('day_of_week')->orderBy('start_time');
    }

    /**
     * Fetch timetable entries for the grid/print views (no pagination).
     */
    public static function fetchEntries(array $filters)
    {
        return self::fetchEntriesQuery($filters)->get();
    }

    /**
     * Compute and attach CSS grid coordinates for each entry so the weekly grid
     * view can position them with plain CSS (no JS charting library).
     */
    public static function applyGridPositions($entries): void
    {
        $windowStart = self::GRID_START_HOUR * 60;
        $windowEnd = self::GRID_END_HOUR * 60;
        $slot = self::GRID_SLOT_MINUTES;

        foreach ($entries as $entry) {
            $startMinutes = self::minutesSinceMidnight($entry->start_time);
            $endMinutes = self::minutesSinceMidnight($entry->end_time);

            $clampedStart = max($windowStart, min($startMinutes, $windowEnd));
            $clampedEnd = max($windowStart, min($endMinutes, $windowEnd));

            $rowStart = intdiv($clampedStart - $windowStart, $slot) + 2; // +1 for 1-index, +1 for header row
            $rowEnd = max($rowStart + 1, intdiv($clampedEnd - $windowStart, $slot) + 2);

            $column = array_search($entry->day_of_week, self::GRID_DAY_ORDER);
            $column = $column === false ? 1 : $column + 2; // +1 for 1-index, +1 for the time-label column

            $entry->grid_row_start = $rowStart;
            $entry->grid_row_end = $rowEnd;
            $entry->grid_column = $column;
            $entry->_overlap_start = $startMinutes;
            $entry->_overlap_end = $endMinutes;
        }

        self::applyOverlapSlots($entries);
    }

    /**
     * Group entries that land in the same day column and overlap in time (e.g. two classes
     * legitimately double-booked into the same venue) and assign each an overlap_index/
     * overlap_count so the grid can render them side-by-side instead of one hiding the other.
     */
    protected static function applyOverlapSlots($entries): void
    {
        $byColumn = collect($entries)->groupBy('grid_column');

        foreach ($byColumn as $columnEntries) {
            $sorted = $columnEntries->sortBy('_overlap_start')->values();
            $clusters = [];

            foreach ($sorted as $entry) {
                $placed = false;

                foreach ($clusters as &$cluster) {
                    $overlapsCluster = collect($cluster)->contains(
                        fn ($member) => $entry->_overlap_start < $member->_overlap_end && $entry->_overlap_end > $member->_overlap_start
                    );

                    if ($overlapsCluster) {
                        $cluster[] = $entry;
                        $placed = true;
                        break;
                    }
                }
                unset($cluster);

                if (!$placed) {
                    $clusters[] = [$entry];
                }
            }

            foreach ($clusters as $cluster) {
                $count = count($cluster);
                foreach ($cluster as $index => $entry) {
                    $entry->overlap_index = $index;
                    $entry->overlap_count = $count;
                }
            }
        }
    }

    /**
     * Hour labels shown down the left edge of the grid.
     */
    public static function gridSlotLabels(): array
    {
        $labels = [];
        for ($minutes = self::GRID_START_HOUR * 60; $minutes < self::GRID_END_HOUR * 60; $minutes += self::GRID_SLOT_MINUTES) {
            $labels[] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        }

        return $labels;
    }

    /**
     * Convert a "H:i" or "H:i:s" time string to minutes since midnight.
     */
    public static function minutesSinceMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    /**
     * A lecturer's workload for a semester: each timetable slot they teach contributes
     * its course's credit hours (e.g. a 3-credit-hour course taught to 5 different
     * classes contributes 3 x 5 = 15 to the total), optionally scoped to one semester.
     *
     * @return array{classes: int, workload: float}
     */
    public static function calculateWorkload(int $lecturerId, ?int $semesterId = null): array
    {
        $query = TimetableEntry::where('timetable_entries.lecturer_id', $lecturerId)
            ->join('courses', 'courses.id', '=', 'timetable_entries.course_id')
            ->when($semesterId, fn ($q) => $q->where('timetable_entries.semester_id', $semesterId));

        return [
            'classes' => (clone $query)->count(),
            'workload' => (float) (clone $query)->sum('courses.credit_hours'),
        ];
    }

    /**
     * A class group's course load: how many distinct courses it takes, and their combined
     * credit hours (each course counted once, regardless of how many timetable slots a
     * week it occupies), optionally scoped to one semester.
     *
     * @return array{courses: int, credit: float}
     */
    public static function classSummary(int $classGroupId, ?int $semesterId = null): array
    {
        $courseIds = TimetableEntry::where('class_group_id', $classGroupId)
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->distinct()
            ->pluck('course_id');

        return [
            'courses' => $courseIds->count(),
            'credit' => (float) Course::whereIn('id', $courseIds)->sum('credit_hours'),
        ];
    }

    /**
     * A lecturer's workload broken down per semester, for the admin lecturer profile page.
     */
    public static function workloadBySemesterForLecturer(int $lecturerId)
    {
        return TimetableEntry::where('timetable_entries.lecturer_id', $lecturerId)
            ->join('courses', 'courses.id', '=', 'timetable_entries.course_id')
            ->join('semesters', 'semesters.id', '=', 'timetable_entries.semester_id')
            ->join('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
            ->selectRaw('semesters.id as semester_id, semesters.name as semester_name, academic_years.name as academic_year_name, COUNT(*) as classes, SUM(courses.credit_hours) as workload')
            ->groupBy('semesters.id', 'semesters.name', 'academic_years.name')
            ->orderByDesc('semesters.id')
            ->get();
    }
}
