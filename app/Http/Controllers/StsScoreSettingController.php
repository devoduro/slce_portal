<?php

namespace App\Http\Controllers;

use App\Models\StsScoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StsScoreSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = StsScoreSetting::orderBy('level')->get();

        return view('sts-score-settings.index', compact('settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sts-score-settings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()->route('sts-score-settings.create')
                ->withErrors($validator)
                ->withInput();
        }

        StsScoreSetting::create($request->only(['level', 'attendance_max', 'project_max', 'assignment_max', 'mid_semester_max']));

        return redirect()->route('sts-score-settings.index')
            ->with('success', 'STS score setting created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StsScoreSetting $stsScoreSetting)
    {
        return view('sts-score-settings.edit', compact('stsScoreSetting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StsScoreSetting $stsScoreSetting)
    {
        $validator = Validator::make($request->all(), $this->rules($stsScoreSetting->id));

        if ($validator->fails()) {
            return redirect()->route('sts-score-settings.edit', $stsScoreSetting)
                ->withErrors($validator)
                ->withInput();
        }

        $stsScoreSetting->update($request->only(['level', 'attendance_max', 'project_max', 'assignment_max', 'mid_semester_max']));

        return redirect()->route('sts-score-settings.index')
            ->with('success', 'STS score setting updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StsScoreSetting $stsScoreSetting)
    {
        $stsScoreSetting->delete();

        return redirect()->route('sts-score-settings.index')
            ->with('success', 'STS score setting deleted successfully.');
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function rules(?int $ignoreId = null): array
    {
        return [
            'level' => 'required|integer|min:100|max:800|unique:sts_score_settings,level' . ($ignoreId ? ",{$ignoreId}" : ''),
            'attendance_max' => 'required|numeric|min:0',
            'project_max' => 'required|numeric|min:0',
            'assignment_max' => 'required|numeric|min:0',
            'mid_semester_max' => 'required|numeric|min:0',
        ];
    }
}
