<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Programme;
use App\Models\Student;
use App\Models\StudentLevelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    /**
     * The academic year a promotion should record the outgoing level against: the year the
     * students have just *completed* at that level, which is not necessarily the year flagged
     * current. Promotions are normally run at a year boundary, and if the new year has already
     * been made current before the promotion is run, the year being left behind is the previous
     * one. Defaulting to the current year in that situation files the snapshot against the year
     * the students are entering - pinning their new year's tuition to their old level, and
     * leaving the year they actually studied with no record at all.
     *
     * Best default available: the most recent year the programme was actually billed for, since
     * a student can only have been charged at a level in a year that has a fee structure.
     * Always overridable on the form - the office knows which year it is closing off.
     */
    protected function defaultCompletedYear(?Programme $programme): ?AcademicYear
    {
        $currentYear = AcademicYear::where('is_current', true)->first();

        $billedYear = $programme
            ? AcademicYear::whereIn('id', function ($query) use ($programme) {
                $query->select('academic_year_id')
                    ->from('fee_structures')
                    ->where('programme_id', $programme->id)
                    ->where('category', 'tuition');
            })
                ->when($currentYear, fn ($query) => $query->where('start_date', '<=', $currentYear->start_date))
                ->orderByDesc('start_date')
                ->first()
            : null;

        return $billedYear ?? $currentYear;
    }

    /**
     * Show the programme + level picker. When a programme is selected, list its
     * levels that currently have active students, with counts, as promotion candidates.
     */
    public function index(Request $request)
    {
        $programmes = Programme::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        $programme = $request->filled('programme_id')
            ? Programme::find($request->programme_id)
            : null;

        $completedYear = $request->filled('completed_academic_year_id')
            ? AcademicYear::find($request->completed_academic_year_id)
            : $this->defaultCompletedYear($programme);

        $levels = [];

        if ($programme) {
            $terminalLevel = $programme->terminalLevel();

            for ($level = 100; $level <= $terminalLevel; $level += 100) {
                $count = Student::where('programme_id', $programme->id)
                    ->where('level', $level)
                    ->where('status', 'active')
                    ->count();

                if ($count > 0) {
                    $levels[] = [
                        'level' => $level,
                        'count' => $count,
                        'is_terminal' => $level === $terminalLevel,
                    ];
                }
            }
        }

        return view('promotions.index', compact('programmes', 'programme', 'levels', 'academicYears', 'completedYear'));
    }

    /**
     * Preview exactly which students will be affected before committing anything.
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'required|integer|min:100',
            'completed_academic_year_id' => 'required|exists:academic_years,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('promotions.index')
                ->withErrors($validator)
                ->withInput();
        }

        $programme = Programme::findOrFail($request->programme_id);
        $completedYear = AcademicYear::findOrFail($request->completed_academic_year_id);
        $level = (int) $request->level;
        $isTerminal = $level >= $programme->terminalLevel();
        $targetLevel = $isTerminal ? null : $level + 100;

        $students = Student::with('classGroup')
            ->where('programme_id', $programme->id)
            ->where('level', $level)
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get();

        if ($students->isEmpty()) {
            return redirect()->route('promotions.index')
                ->with('error', 'No active students found at that level for this programme.');
        }

        return view('promotions.preview', compact('programme', 'level', 'targetLevel', 'isTerminal', 'students', 'completedYear'));
    }

    /**
     * Commit the promotion: bump level (or mark graduated at the terminal level) and clear
     * the class assignment so the vacated class group is free to take in new intake.
     *
     * Only ever touches students.level/class_group_id/status - results, registrations,
     * continuous assessment and arrears are keyed by academic_year_id (not level or class
     * group) and are never read or written here, so continuing students' historical
     * records are left untouched. It does, however, snapshot the level students are being
     * promoted out of (see below) - that's what keeps their *fees* untouched too.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'required|integer|min:100',
            'completed_academic_year_id' => 'required|exists:academic_years,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('promotions.index')
                ->withErrors($validator)
                ->withInput();
        }

        $programme = Programme::findOrFail($request->programme_id);
        $completedYear = AcademicYear::findOrFail($request->completed_academic_year_id);
        $level = (int) $request->level;
        $isTerminal = $level >= $programme->terminalLevel();

        $count = 0;

        DB::transaction(function () use ($programme, $completedYear, $level, $isTerminal, &$count) {
            $studentIds = Student::where('programme_id', $programme->id)
                ->where('level', $level)
                ->where('status', 'active')
                ->pluck('id');

            $count = $studentIds->count();

            if ($count === 0) {
                return;
            }

            // Snapshot the level these students are being promoted OUT of against the year they
            // completed at that level, before their level column moves on to the next one.
            // Without this, a fee lookup for that year would later use their new
            // (post-promotion) level instead of the one they actually studied - and were
            // billed - at. The year is chosen on the form rather than assumed to be whichever
            // year is flagged current: promotions are often run once the new year has already
            // been opened, and filing the snapshot against that year records the opposite of
            // the truth - the old level for the year they are about to study at the new one.
            $now = now();

            StudentLevelHistory::upsert(
                $studentIds->map(fn ($id) => [
                    'student_id' => $id,
                    'academic_year_id' => $completedYear->id,
                    'level' => $level,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
                ['student_id', 'academic_year_id'],
                ['level', 'updated_at']
            );

            $scope = Student::whereIn('id', $studentIds);

            if ($isTerminal) {
                $scope->update([
                    'class_group_id' => null,
                    'status' => 'graduated',
                    'graduated_academic_year_id' => $completedYear->id,
                ]);
            } else {
                $scope->update(['level' => $level + 100, 'class_group_id' => null]);
            }
        });

        if ($count === 0) {
            return redirect()->route('promotions.index')
                ->with('error', 'No active students found at that level for this programme — they may have already been promoted.');
        }

        $message = $isTerminal
            ? "{$count} student(s) marked as graduated from Level {$level} at the end of {$completedYear->name}."
            : "{$count} student(s) promoted from Level {$level} to Level " . ($level + 100) . ", recorded as having studied {$completedYear->name} at Level {$level}. Their previous class assignment was cleared — reassign them via Classes > Assign Students.";

        return redirect()->route('promotions.index')->with('success', $message);
    }
}
