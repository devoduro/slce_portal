<?php

namespace App\Http\Controllers;

use App\Services\AdmissionEligibilityService;
use App\Services\AdmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicantController extends Controller
{
    /**
     * The applicant's own status/bill/payment dashboard.
     */
    public function dashboard()
    {
        $admission = Auth::user()->admission()->with(['programme', 'academicYear', 'billItems', 'payments'])->firstOrFail();

        $totalBilled = $admission->totalBilled();
        $totalPaid = $admission->totalPaid();
        $outstanding = $admission->outstandingBalance();
        $canProceed = AdmissionEligibilityService::canProceedToProcessing($admission);

        return view('applicant.dashboard', compact('admission', 'totalBilled', 'totalPaid', 'outstanding', 'canProceed'));
    }

    public function editProfile()
    {
        $admission = Auth::user()->admission()->firstOrFail();
        AdmissionEligibilityService::assertCanProceedToProcessing($admission);

        return view('applicant.profile-edit', compact('admission'));
    }

    public function updateProfile(Request $request)
    {
        $admission = Auth::user()->admission()->firstOrFail();

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'hometown' => ['nullable', 'string', 'max:255'],
            'gps_address' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],
            'emergency_contact_relationship' => ['required', 'string', 'max:50'],
        ]);

        AdmissionService::updateProfile($admission, $data);

        return redirect()->route('applicant.profile.edit')->with('success', 'Profile updated successfully.');
    }

    public function confirmProfile()
    {
        $admission = Auth::user()->admission()->firstOrFail();

        AdmissionService::confirmProfile($admission);

        return redirect()->route('applicant.dashboard')->with('success', 'Your information has been confirmed and locked.');
    }

    public function markReported()
    {
        $admission = Auth::user()->admission()->firstOrFail();

        AdmissionService::markReported($admission);

        return redirect()->route('applicant.dashboard')->with('success', 'You have been marked as reported. Awaiting final approval.');
    }

    /**
     * Download the applicant's own admission letter (initial, or final once approved).
     */
    public function letter()
    {
        $admission = Auth::user()->admission()->with(['programme', 'academicYear'])->firstOrFail();

        return AdmissionController::buildLetterPdf($admission);
    }

    /**
     * Download the applicant's own printable Acceptance Form.
     */
    public function acceptanceForm()
    {
        $admission = Auth::user()->admission()->with(['programme', 'academicYear'])->firstOrFail();

        return AdmissionController::buildAcceptanceFormPdf($admission);
    }
}
