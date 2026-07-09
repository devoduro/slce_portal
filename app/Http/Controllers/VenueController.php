<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $venues = Venue::withCount('timetableEntries')->orderBy('name')->paginate(20);

        return view('venues.index', compact('venues'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('venues.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()->route('venues.create')
                ->withErrors($validator)
                ->withInput();
        }

        Venue::create($request->only(['name', 'capacity', 'location', 'max_concurrent_classes']));

        return redirect()->route('venues.index')
            ->with('success', 'Venue created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Venue $venue)
    {
        return view('venues.edit', compact('venue'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Venue $venue)
    {
        $validator = Validator::make($request->all(), $this->rules($venue->id));

        if ($validator->fails()) {
            return redirect()->route('venues.edit', $venue)
                ->withErrors($validator)
                ->withInput();
        }

        $venue->update($request->only(['name', 'capacity', 'location', 'max_concurrent_classes']));

        return redirect()->route('venues.index')
            ->with('success', 'Venue updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venue $venue)
    {
        if ($venue->timetableEntries()->count() > 0) {
            return redirect()->route('venues.index')
                ->with('error', 'Cannot delete a venue that is used in the timetable.');
        }

        $venue->delete();

        return redirect()->route('venues.index')
            ->with('success', 'Venue deleted successfully.');
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function rules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('venues', 'name')->ignore($ignoreId)],
            'capacity' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'max_concurrent_classes' => 'required|integer|min:1|max:10',
        ];
    }
}
