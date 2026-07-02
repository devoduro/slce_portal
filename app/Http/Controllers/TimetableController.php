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

        $filters = $request->only(['class_group_id', 'lecturer_id', 'department_id', 'semester_id']);

        $showGrid = $request->filled('semester_id')
            && ($request->filled('class_group_id') || $request->filled('lecturer_id') || $request->filled('department_id'));

        $entries = collect();
        $paginatedEntries = null;
        $slotLabels = [];

        if ($showGrid) {
            $entries = $this->fetchEntries($filters);
            $this->applyGridPositions($entries);
            $slotLabels = $this->gridSlotLabels();
        } else {
            $paginatedEntries = $this->fetchEntriesQuery($filters)
                ->paginate(20)->withQueryString();
        }

        return view('timetable.index', compact(
            'entries', 'paginatedEntries', 'classGroups', 'lecturers', 'departments', 'semesters', 'showGrid', 'slotLabels'
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
        ]);

        $hasScope = $request->filled('class_group_id') || $request->filled('lecturer_id') || $request->filled('department_id');

        if ($validator->fails() || !$hasScope) {
            return redirect()->route('timetable.index')
                ->with('error', 'Select a class, lecturer or department, and a semester, before printing.');
        }

        $semester = Semester::with('academicYear')->findOrFail($request->semester_id);
        $classGroup = $request->filled('class_group_id') ? ClassGroup::find($request->class_group_id) : null;
        $lecturer = $request->filled('lecturer_id') ? Lecturer::find($request->lecturer_id) : null;
        $department = $request->filled('department_id') ? Department::find($request->department_id) : null;

        $entries = $this->fetchEntries($request->only(['class_group_id', 'lecturer_id', 'department_id', 'semester_id']));
        $this->applyGridPositions($entries);
        $slotLabels = $this->gridSlotLabels();

        $settings = DB::table('settings')->where('category', 'institution')->pluck('value', 'key')->toArray();

        return view('timetable.print', compact('entries', 'slotLabels', 'semester', 'classGroup', 'lecturer', 'department', 'settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $classGroups = ClassGroup::orderBy('name')->get();
        $courses = Course::orderBy('code')->get();
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

        TimetableEntry::create($request->only([
            'class_group_id', 'course_id', 'lecturer_id', 'venue_id', 'semester_id', 'day_of_week', 'start_time', 'end_time',
        ]));

        return redirect()->route('timetable.index')
            ->with('success', 'Timetable entry created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimetableEntry $timetable)
    {
        $classGroups = ClassGroup::orderBy('name')->get();
        $courses = Course::orderBy('code')->get();
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

        $timetable->update($request->only([
            'class_group_id', 'course_id', 'lecturer_id', 'venue_id', 'semester_id', 'day_of_week', 'start_time', 'end_time',
        ]));

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
            'venue_id' => 'required|exists:venues,id',
            'semester_id' => 'required|exists:semesters,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ];
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

        // A venue may legitimately host up to 2 simultaneous classes (e.g. a shared
        // or split hall) — only reject once a 3rd booking would collide.
        $venueBookingCount = TimetableEntry::where('venue_id', $request->venue_id)
            ->where($overlap)
            ->count();

        if ($venueBookingCount >= 2) {
            return 'This venue already has 2 classes booked at an overlapping time on this day.';
        }

        return null;
    }

    /**
     * Build the filtered timetable entries query shared by the grid, flat-list and
     * print views, filtered by any combination of class group, lecturer, department
     * (via the lecturer's department) and semester.
     */
    protected function fetchEntriesQuery(array $filters)
    {
        return TimetableEntry::with(['classGroup', 'course', 'lecturer.department', 'venue', 'semester'])
            ->when(!empty($filters['class_group_id']), fn ($q) => $q->where('class_group_id', $filters['class_group_id']))
            ->when(!empty($filters['lecturer_id']), fn ($q) => $q->where('lecturer_id', $filters['lecturer_id']))
            ->when(!empty($filters['department_id']), fn ($q) => $q->whereHas('lecturer', fn ($lq) => $lq->where('department_id', $filters['department_id'])))
            ->when(!empty($filters['semester_id']), fn ($q) => $q->where('semester_id', $filters['semester_id']))
            ->orderBy('day_of_week')->orderBy('start_time');
    }

    /**
     * Fetch timetable entries for the grid/print views (no pagination).
     */
    protected function fetchEntries(array $filters)
    {
        return $this->fetchEntriesQuery($filters)->get();
    }

    /**
     * Compute and attach CSS grid coordinates for each entry so the weekly grid
     * view can position them with plain CSS (no JS charting library).
     */
    protected function applyGridPositions($entries): void
    {
        $windowStart = self::GRID_START_HOUR * 60;
        $windowEnd = self::GRID_END_HOUR * 60;
        $slot = self::GRID_SLOT_MINUTES;

        foreach ($entries as $entry) {
            $startMinutes = $this->minutesSinceMidnight($entry->start_time);
            $endMinutes = $this->minutesSinceMidnight($entry->end_time);

            $clampedStart = max($windowStart, min($startMinutes, $windowEnd));
            $clampedEnd = max($windowStart, min($endMinutes, $windowEnd));

            $rowStart = intdiv($clampedStart - $windowStart, $slot) + 2; // +1 for 1-index, +1 for header row
            $rowEnd = max($rowStart + 1, intdiv($clampedEnd - $windowStart, $slot) + 2);

            $column = array_search($entry->day_of_week, self::GRID_DAY_ORDER);
            $column = $column === false ? 1 : $column + 2; // +1 for 1-index, +1 for the time-label column

            $entry->grid_row_start = $rowStart;
            $entry->grid_row_end = $rowEnd;
            $entry->grid_column = $column;
        }
    }

    /**
     * Hour labels shown down the left edge of the grid.
     */
    protected function gridSlotLabels(): array
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
    protected function minutesSinceMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
