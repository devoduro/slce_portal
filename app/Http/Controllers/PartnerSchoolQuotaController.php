<?php

namespace App\Http\Controllers;

use App\Models\PartnerSchool;
use App\Models\PartnerSchoolQuota;
use App\Models\StsTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Per-term partner school quotas: what each school agreed to take, for one batch, at each level.
 *
 * The point of scoping an allocation to a term is that it can follow the batch. A cohort placed
 * at Level 300 last term is at Level 400 this term, still at the same school - carryForward()
 * writes this term's rows one level up so the allocation moves with them, while last term's rows
 * stay put as the record of what that term actually was.
 */
class PartnerSchoolQuotaController extends Controller
{
    /**
     * Show one term's quotas across every school in scope, editable in place.
     */
    public function index(Request $request)
    {
        $stsTerms = StsTerm::orderByDesc('id')->get();
        $currentTerm = $stsTerms->firstWhere('is_current', true);

        $term = $request->filled('sts_term_id')
            ? $stsTerms->firstWhere('id', (int) $request->sts_term_id)
            : $currentTerm;

        $previousTerm = $term
            ? $stsTerms->sortBy('id')->last(fn (StsTerm $t) => $t->id < $term->id)
            : null;

        $schools = collect();
        $capacities = [];
        $placedCounts = collect();
        $hasOwnRows = false;

        if ($term) {
            $query = PartnerSchool::query()->usableInTerm($term->id);

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $schools = $query->orderBy('name')->get();
            $capacities = PartnerSchool::capacitiesFor($schools, $term);
            $placedCounts = PartnerSchool::placedCountsFor($schools, $term);

            // Whether this term has any quotas of its own yet, or is still falling back to the
            // schools' original columns - worth saying plainly before someone edits.
            $hasOwnRows = PartnerSchoolQuota::where('sts_term_id', $term->id)
                ->whereIn('partner_school_id', $schools->pluck('id'))
                ->exists();
        }

        return view('partner-schools.quotas', compact(
            'stsTerms', 'term', 'previousTerm', 'schools', 'capacities', 'placedCounts', 'hasOwnRows'
        ));
    }

    /**
     * Save the edited quotas for one term.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sts_term_id' => 'required|exists:sts_terms,id',
            'quotas' => 'required|array',
            'quotas.*' => 'array',
            'quotas.*.*' => 'nullable|integer|min:0|max:9999',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $termId = (int) $request->sts_term_id;
        $saved = 0;

        DB::transaction(function () use ($request, $termId, &$saved) {
            foreach ((array) $request->input('quotas', []) as $schoolId => $levels) {
                foreach ((array) $levels as $level => $capacity) {
                    if (!in_array((int) $level, PartnerSchool::QUOTA_LEVELS, true)) {
                        continue;
                    }

                    PartnerSchoolQuota::updateOrCreate(
                        [
                            'partner_school_id' => (int) $schoolId,
                            'sts_term_id' => $termId,
                            'level' => (int) $level,
                        ],
                        ['capacity' => (int) ($capacity ?? 0)]
                    );

                    $saved++;
                }
            }
        });

        return redirect()->route('partner-schools.quotas.index', ['sts_term_id' => $termId])
            ->with('success', "Saved {$saved} quota figure(s) for this term.");
    }

    /**
     * Copy a previous term's quotas into this one, moved up a level to follow the promoted batch.
     *
     * Level 300's allocation becomes Level 400's, 200's becomes 300's, and so on. The Level 100
     * allocation is carried across unchanged rather than shifted from nothing, since a fresh
     * intake needs the same first-year places the last intake had.
     *
     * Never overwrites a figure this term already holds: an allocation the office has already
     * negotiated for this batch outranks anything inferred from last year.
     */
    public function carryForward(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sts_term_id' => 'required|exists:sts_terms,id',
            'from_sts_term_id' => 'required|exists:sts_terms,id|different:sts_term_id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $term = StsTerm::findOrFail($request->sts_term_id);
        $from = StsTerm::findOrFail($request->from_sts_term_id);

        $schools = PartnerSchool::usableInTerm($term->id)->get();
        $source = PartnerSchool::capacitiesFor($schools, $from);
        $existing = PartnerSchoolQuota::where('sts_term_id', $term->id)
            ->whereIn('partner_school_id', $schools->pluck('id'))
            ->get()
            ->groupBy('partner_school_id');

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($schools, $source, $existing, $term, &$created, &$skipped) {
            foreach ($schools as $school) {
                $shifted = [];

                $accepted = PartnerSchool::levelsForType($school->type, $term);

                foreach (PartnerSchool::QUOTA_LEVELS as $level) {
                    // A level this school's type never places at gets a nil allocation, whatever
                    // last term held - an internship school has no business carrying a Level 200
                    // figure forward.
                    if (!in_array($level, $accepted, true)) {
                        $shifted[$level] = 0;

                        continue;
                    }

                    // Level 100 keeps its own figure (a fresh intake, not a promoted one);
                    // every other level inherits from the level below it last term.
                    $sourceLevel = $level === 100 ? 100 : $level - 100;
                    $shifted[$level] = (int) ($source[$school->id][$sourceLevel] ?? 0);
                }

                // Nothing to say about this school - leave it on its existing figures rather
                // than writing it a row set of zeros.
                if (array_sum($shifted) === 0) {
                    continue;
                }

                $held = $existing->get($school->id, collect())->keyBy('level');

                foreach ($shifted as $level => $capacity) {
                    if ($held->has($level)) {
                        $skipped++;

                        continue;
                    }

                    // Written for every level, zeros included: a term states a school's whole
                    // allocation or none of it (see PartnerSchool::capacityForLevel()). Skipping
                    // the zeros would leave the level this batch has just moved out of falling
                    // back to last year's figure, advertising places they are still sitting in.
                    PartnerSchoolQuota::create([
                        'partner_school_id' => $school->id,
                        'sts_term_id' => $term->id,
                        'level' => $level,
                        'capacity' => $capacity,
                    ]);

                    $created++;
                }
            }
        });

        if ($created === 0) {
            return back()->with('error', $skipped > 0
                ? "Nothing carried forward — this term already holds its own figures for all {$skipped} of those levels."
                : "{$from->name} has no quotas to carry forward.");
        }

        $message = "Carried {$created} allocation(s) forward from {$from->name}, each moved up a level to follow the promoted batch.";

        if ($skipped > 0) {
            $message .= " Left {$skipped} figure(s) this term already had untouched.";
        }

        return redirect()->route('partner-schools.quotas.index', ['sts_term_id' => $term->id])
            ->with('success', $message);
    }
}
