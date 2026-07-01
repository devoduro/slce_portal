<?php

namespace App\Http\Controllers;

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Semester;
use App\Models\TimetableEntry;
use Illuminate\Http\Request;
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
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TimetableEntry::with(['classGroup', 'course', 'semester']);

        if ($request->filled('class_group_id')) {
            $query->where('class_group_id', $request->class_group_id);
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        $entries = $query->orderBy('day_of_week')->orderBy('start_time')->paginate(20)->withQueryString();

        $classGroups = ClassGroup::orderBy('name')->get();
        $semesters = Semester::orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();

        return view('timetable.index', compact('entries', 'classGroups', 'semesters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $classGroups = ClassGroup::orderBy('name')->get();
        $courses = Course::orderBy('code')->get();
        $semesters = Semester::orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();

        return view('timetable.create', compact('classGroups', 'courses', 'semesters'));
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
            'class_group_id', 'course_id', 'semester_id', 'day_of_week', 'start_time', 'end_time', 'venue',
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
        $semesters = Semester::orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();

        return view('timetable.edit', ['entry' => $timetable, 'classGroups' => $classGroups, 'courses' => $courses, 'semesters' => $semesters]);
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
            'class_group_id', 'course_id', 'semester_id', 'day_of_week', 'start_time', 'end_time', 'venue',
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
            'semester_id' => 'required|exists:semesters,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'venue' => 'required|string|max:255',
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

        $venueClash = TimetableEntry::where('venue', $request->venue)
            ->where($overlap)
            ->exists();

        if ($venueClash) {
            return 'This venue is already booked for an overlapping time on this day.';
        }

        return null;
    }
}
