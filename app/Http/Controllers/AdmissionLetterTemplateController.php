<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Admission;
use App\Models\AdmissionLetterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class AdmissionLetterTemplateController extends Controller
{
    /**
     * One row per academic year, flagging whether it has a saved custom letter yet.
     */
    public function index()
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $templates = AdmissionLetterTemplate::pluck('id', 'academic_year_id');

        return view('admission-letter-templates.index', compact('academicYears', 'templates'));
    }

    public function edit(AcademicYear $academicYear)
    {
        $template = AdmissionLetterTemplate::where('academic_year_id', $academicYear->id)->first();

        $provisionalBody = $template->provisional_body ?? AdmissionLetterTemplate::defaultBody(false);
        $finalBody = $template->final_body ?? AdmissionLetterTemplate::defaultBody(true);
        $placeholders = AdmissionLetterTemplate::placeholders();

        // Lets the admin preview against a real applicant's real data (photo, name,
        // programme) instead of only the generic sample - scoped to this academic year
        // since that's the letter being edited.
        $admissions = Admission::where('academic_year_id', $academicYear->id)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'applicant_number']);

        return view('admission-letter-templates.edit', compact('academicYear', 'template', 'provisionalBody', 'finalBody', 'placeholders', 'admissions'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $data = $request->validate([
            'provisional_body' => 'nullable|string',
            'final_body' => 'nullable|string',
        ]);

        AdmissionLetterTemplate::updateOrCreate(
            ['academic_year_id' => $academicYear->id],
            [
                'provisional_body' => $data['provisional_body'] ?: null,
                'final_body' => $data['final_body'] ?: null,
                'updated_by' => Auth::id(),
            ]
        );

        return redirect()->route('admission-letter-templates.edit', $academicYear)
            ->with('success', 'Admission letter saved for ' . $academicYear->name . '.');
    }

    /**
     * Preview the letter using whatever text is currently in the edit form - not
     * necessarily saved yet - rendered against either a real admission for this
     * academic year (if one is picked) or sample placeholder data.
     */
    public function preview(Request $request, AcademicYear $academicYear)
    {
        $data = $request->validate([
            'variant' => 'required|in:provisional,final',
            'body' => 'nullable|string',
            'admission_id' => 'nullable|integer|exists:admissions,id',
        ]);

        $isFinal = $data['variant'] === 'final';
        $body = $data['body'] ?: AdmissionLetterTemplate::defaultBody($isFinal);

        $admission = $data['admission_id'] ?? null
            ? Admission::with(['programme', 'academicYear'])->find($data['admission_id'])
            : null;

        $settings = [];
        foreach (DB::table('settings')->where('category', 'institution')->get() as $setting) {
            $settings[$setting->key] = $setting->value;
        }
        foreach (DB::table('settings')->where('category', 'admission')->get() as $setting) {
            $settings[$setting->key] = $setting->value;
        }

        if ($admission) {
            // A real applicant - the actual amounts they were really billed/paid.
            $bodyHtml = AdmissionLetterTemplate::render($body, [
                'full_name' => $admission->full_name,
                'title' => $admission->title,
                'applicant_number' => $admission->applicant_number,
                'programme' => $admission->programme->name ?? '',
                'academic_year' => $admission->academicYear->name ?? $academicYear->name,
                'level' => (string) $admission->level,
                'institution_name' => $settings['institution_name'] ?? config('app.name'),
                'total_billed' => number_format($admission->totalBilled(), 2),
                'amount_paid' => number_format($admission->totalPaid(), 2),
                'outstanding_balance' => number_format($admission->outstandingBalance(), 2),
                'bill_items' => AdmissionLetterTemplate::billItemsSummary($admission),
            ]);
        } else {
            // No real applicant picked - a throwaway sample Admission (so the blade
            // template's $admission->full_name etc. still resolve) plus made-up but
            // realistic bill figures, since a sample Admission has no real bill items.
            $admission = new Admission([
                'title' => 'Miss',
                'full_name' => 'Sample Applicant',
                'applicant_number' => 'APP' . $academicYear->name[0] . '0001',
                'level' => 100,
                'admission_status' => $isFinal ? Admission::STATUS_APPROVED : Admission::STATUS_OFFERED,
                'payment_status' => $isFinal ? Admission::PAYMENT_CONFIRMED : Admission::PAYMENT_NOT_BILLED,
            ]);
            $admission->setRelation('programme', new \App\Models\Programme(['name' => 'B.Ed Sample Programme']));
            $admission->setRelation('academicYear', $academicYear);
            $admission->created_at = now();

            $bodyHtml = AdmissionLetterTemplate::render($body, [
                'full_name' => $admission->full_name,
                'title' => $admission->title,
                'applicant_number' => $admission->applicant_number,
                'programme' => 'B.Ed Sample Programme',
                'academic_year' => $academicYear->name,
                'level' => '100',
                'institution_name' => $settings['institution_name'] ?? config('app.name'),
                'total_billed' => '4,194.42',
                'amount_paid' => '0.00',
                'outstanding_balance' => '4,194.42',
                'bill_items' => 'School Fees (GH¢3,000.00), Examination Fee (GH¢1,000.00), Mattress Fee (GH¢194.42)',
            ]);
        }

        $photoDataUri = \App\Http\Controllers\AdmissionController::photoDataUri($admission) ?? \App\Http\Controllers\AdmissionController::defaultAvatarDataUri();

        $pdf = PDF::loadView('admissions.letters.admission-letter', [
            'admission' => $admission,
            'settings' => $settings,
            'isFinal' => $isFinal,
            'photoDataUri' => $photoDataUri,
            'bodyHtml' => $bodyHtml,
        ]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true]);

        return $pdf->stream('preview.pdf');
    }
}
