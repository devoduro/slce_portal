<?php

namespace App\Http\Controllers;

use App\Models\CaScoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CaScoreSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = CaScoreSetting::orderBy('level')->get();

        return view('ca-score-settings.index', compact('settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('ca-score-settings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()->route('ca-score-settings.create')
                ->withErrors($validator)
                ->withInput();
        }

        CaScoreSetting::create($request->only(['level', 'attendance_max', 'project_max', 'assignment_max', 'mid_semester_max']));

        return redirect()->route('ca-score-settings.index')
            ->with('success', 'CA score setting created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CaScoreSetting $caScoreSetting)
    {
        return view('ca-score-settings.edit', compact('caScoreSetting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CaScoreSetting $caScoreSetting)
    {
        $validator = Validator::make($request->all(), $this->rules($caScoreSetting->id));

        if ($validator->fails()) {
            return redirect()->route('ca-score-settings.edit', $caScoreSetting)
                ->withErrors($validator)
                ->withInput();
        }

        $caScoreSetting->update($request->only(['level', 'attendance_max', 'project_max', 'assignment_max', 'mid_semester_max']));

        return redirect()->route('ca-score-settings.index')
            ->with('success', 'CA score setting updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CaScoreSetting $caScoreSetting)
    {
        $caScoreSetting->delete();

        return redirect()->route('ca-score-settings.index')
            ->with('success', 'CA score setting deleted successfully.');
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function rules(?int $ignoreId = null): array
    {
        return [
            'level' => 'required|integer|min:100|max:800|unique:ca_score_settings,level' . ($ignoreId ? ",{$ignoreId}" : ''),
            'attendance_max' => 'required|numeric|min:0',
            'project_max' => 'required|numeric|min:0',
            'assignment_max' => 'required|numeric|min:0',
            'mid_semester_max' => 'required|numeric|min:0',
        ];
    }
}
