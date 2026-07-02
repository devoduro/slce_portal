<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LecturerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Lecturer::withCount('courses')->with('department');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('staff_id', 'like', "%{$search}%");
            });
        }

        $lecturers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('lecturers.index', compact('lecturers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();

        return view('lecturers.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()->route('lecturers.create')
                ->withErrors($validator)
                ->withInput();
        }

        Lecturer::create($request->only(['name', 'email', 'phone', 'staff_id', 'department_id']));

        return redirect()->route('lecturers.index')
            ->with('success', 'Lecturer added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lecturer $lecturer)
    {
        $lecturer->load(['department', 'courses.semester', 'timetableEntries.classGroup', 'timetableEntries.venue', 'user']);

        return view('lecturers.show', compact('lecturer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lecturer $lecturer)
    {
        $departments = Department::orderBy('name')->get();

        return view('lecturers.edit', compact('lecturer', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lecturer $lecturer)
    {
        $validator = Validator::make($request->all(), $this->rules($lecturer->id));

        if ($validator->fails()) {
            return redirect()->route('lecturers.edit', $lecturer)
                ->withErrors($validator)
                ->withInput();
        }

        $lecturer->update($request->only(['name', 'email', 'phone', 'staff_id', 'department_id']));

        return redirect()->route('lecturers.index')
            ->with('success', 'Lecturer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lecturer $lecturer)
    {
        if ($lecturer->courses()->count() > 0) {
            return redirect()->route('lecturers.index')
                ->with('error', 'Cannot delete a lecturer who is assigned to one or more courses.');
        }

        if ($lecturer->timetableEntries()->count() > 0) {
            return redirect()->route('lecturers.index')
                ->with('error', 'Cannot delete a lecturer who is assigned to one or more timetable entries.');
        }

        $lecturer->delete();

        return redirect()->route('lecturers.index')
            ->with('success', 'Lecturer deleted successfully.');
    }

    /**
     * Create a portal login for the lecturer.
     */
    public function createUserAccount(Lecturer $lecturer)
    {
        if ($lecturer->user) {
            return redirect()->route('lecturers.show', $lecturer)
                ->with('warning', 'This lecturer already has a user account.');
        }

        if (!$lecturer->email) {
            return redirect()->route('lecturers.show', $lecturer)
                ->with('error', 'This lecturer has no email address on file. Add one before creating a login.');
        }

        $temporaryPassword = Str::password(12);

        try {
            User::create([
                'name' => $lecturer->name,
                'email' => $lecturer->email,
                'password' => Hash::make($temporaryPassword),
                'role' => 'admin',
                'lecturer_id' => $lecturer->id,
                'first_login' => true,
            ])->assignRole('Lecturer');

            return redirect()->route('lecturers.show', $lecturer)
                ->with('success', "User account created successfully. Temporary password: {$temporaryPassword} (share this with the lecturer securely — it will not be shown again).");
        } catch (\Exception $e) {
            return redirect()->route('lecturers.show', $lecturer)
                ->with('error', 'Failed to create user account. Error: ' . $e->getMessage());
        }
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function rules(?int $ignoreId = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('lecturers', 'email')->ignore($ignoreId)],
            'phone' => 'nullable|string|max:20',
            'staff_id' => ['nullable', 'string', 'max:50', Rule::unique('lecturers', 'staff_id')->ignore($ignoreId)],
            'department_id' => 'nullable|exists:departments,id',
        ];
    }
}
