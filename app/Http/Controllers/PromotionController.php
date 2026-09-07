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
     * Show the programme + level picker. When a programme is selected, list its
     * levels that currently have active students, with counts, as promotion candidates.
     */
    public function index(Request $request)
    {
        $programmes = Programme::orderBy('name')->get();

        $programme = $request->filled('programme_id')
            ? Programme::find($request->programme_id)
            : null;

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

        return view('promotions.index', compact('programmes', 'programme', 'levels'));
    }

    /**
     * Preview exactly which students will be affected before committing anything.
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'required|integer|min:100',
        ]);

        if ($validator->fails()) {
            return redirect()->route('promotions.index')
                ->withErrors($validator)
                ->withInput();
        }

        $programme = Programme::findOrFail($request->programme_id);
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

        return view('promotions.preview', compact('programme', 'level', 'targetLevel', 'isTerminal', 'students'));
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
        ]);

        if ($validator->fails()) {
            return redirect()->route('promotions.index')
                ->withErrors($validator)
                ->withInput();
        }

        $programme = Programme::findOrFail($request->programme_id);
        $level = (int) $request->level;
        $isTerminal = $level >= $programme->terminalLevel();

        $count = 0;

        DB::transaction(function () use ($programme, $level, $isTerminal, &$count) {
            $studentIds = Student::where('programme_id', $programme->id)
                ->where('level', $level)
                ->where('status', 'active')
                ->pluck('id');

            $count = $studentIds->count();

            if ($count === 0) {
                return;
            }

            $currentAcademicYear = AcademicYear::where('is_current', true)->first();

            // Snapshot the level these students are being promoted OUT of, for the academic
            // year that's ending, before their level column moves on to the next one. Without
            // this, a fee lookup for this year would later use their new (post-promotion)
            // level instead of the one they actually studied - and were billed - at.
            if ($currentAcademicYear) {
                $now = now();

                StudentLevelHistory::upsert(
                    $studentIds->map(fn ($id) => [
                        'student_id' => $id,
                        'academic_year_id' => $currentAcademicYear->id,
                        'level' => $level,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all(),
                    ['student_id', 'academic_year_id'],
                    ['level', 'updated_at']
                );
            }

            $scope = Student::whereIn('id', $studentIds);

            if ($isTerminal) {
                $scope->update([
                    'class_group_id' => null,
                    'status' => 'graduated',
                    'graduated_academic_year_id' => $currentAcademicYear?->id,
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
            ? "{$count} student(s) marked as graduated from Level {$level}."
            : "{$count} student(s) promoted from Level {$level} to Level " . ($level + 100) . '. Their previous class assignment was cleared — reassign them via Classes > Assign Students.';

        return redirect()->route('promotions.index')->with('success', $message);
    }
}
