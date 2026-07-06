<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LecturerPortalController extends Controller
{
    /**
     * Show the authenticated lecturer's own profile edit form.
     */
    public function profile()
    {
        $lecturer = $this->authLecturerOrAbort();

        return view('lecturers.my-profile', compact('lecturer'));
    }

    /**
     * Update the authenticated lecturer's own contact details/photo.
     * Name, staff ID and department stay admin-managed (edited via lecturers.edit).
     */
    public function updateProfile(Request $request)
    {
        $lecturer = $this->authLecturerOrAbort();

        $validator = Validator::make($request->all(), [
            'email' => ['nullable', 'email', 'max:255', Rule::unique('lecturers', 'email')->ignore($lecturer->id)],
            'phone' => 'nullable|string|max:20',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->route('lecturer.profile.edit')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->only(['email', 'phone']);

        if ($request->hasFile('profile_photo')) {
            if ($lecturer->profile_photo) {
                Storage::disk('public')->delete($lecturer->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('lecturer-photos', 'public');
        }

        $lecturer->update($data);

        return redirect()->route('lecturer.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Show the authenticated lecturer's own weekly timetable.
     */
    public function timetable(Request $request)
    {
        $lecturer = $this->authLecturerOrAbort();

        $semesters = Semester::orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();
        $semester = $request->filled('semester_id')
            ? $semesters->firstWhere('id', (int) $request->semester_id)
            : $semesters->firstWhere('is_current', true);

        $entries = collect();
        $slotLabels = [];
        $workload = ['classes' => 0, 'workload' => 0.0];

        if ($semester) {
            $entries = TimetableController::fetchEntries(['lecturer_id' => $lecturer->id, 'semester_id' => $semester->id]);
            TimetableController::applyGridPositions($entries);
            $slotLabels = TimetableController::gridSlotLabels();
            $workload = TimetableController::calculateWorkload($lecturer->id, $semester->id);
        }

        return view('lecturers.my-timetable', compact('lecturer', 'semesters', 'semester', 'entries', 'slotLabels', 'workload'));
    }

    /**
     * Print the authenticated lecturer's own timetable. Not gated by the admin-only
     * manage-timetable permission since this only ever shows the lecturer their own schedule.
     */
    public function printTimetable(Request $request)
    {
        $lecturer = $this->authLecturerOrAbort();

        $semesterId = $request->filled('semester_id')
            ? (int) $request->semester_id
            : optional(Semester::where('is_current', true)->first())->id;

        abort_unless($semesterId, 404, 'No semester is available to print.');

        return TimetableController::buildPrintView(['lecturer_id' => $lecturer->id, 'semester_id' => $semesterId]);
    }

    /**
     * Resolve the lecturer profile linked to the authenticated user, or deny access.
     */
    protected function authLecturerOrAbort()
    {
        $lecturer = Auth::user()->lecturer;

        abort_unless($lecturer, 403, 'This area is only available to lecturer accounts.');

        return $lecturer;
    }
}
