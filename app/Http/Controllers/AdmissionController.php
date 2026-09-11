<?php

namespace App\Http\Controllers;

use App\Exports\AdmissionsExport;
use App\Exports\AdmissionTemplateExport;
use App\Imports\AdmissionImport;
use App\Models\Admission;
use App\Models\FeeCategory;
use App\Models\Programme;
use App\Models\AcademicYear;
use App\Services\AdmissionEligibilityService;
use App\Services\AdmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class AdmissionController extends Controller
{
    /**
     * Page sizes the admin can switch between on the admission queue.
     */
    public const PER_PAGE_OPTIONS = [50, 100, 200, 300, 400];

    /**
     * Admission queue for Admission Officers/Admins.
     */
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

        $perPage = (int) $request->input('per_page', 50);
        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 50;
        }

        $admissions = $query->paginate($perPage)->withQueryString();

        $programmes = Programme::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $halls = Admission::whereNotNull('hall')->where('hall', '!=', '')->distinct()->orderBy('hall')->pluck('hall');

        return view('admissions.index', compact('admissions', 'programmes', 'academicYears', 'halls', 'perPage'));
    }

    /**
     * Shared by index(), the exports, and bulk-destroy's "select all matching filter"
     * option, so every one of them agrees on what the current filters mean.
     */
    protected function filteredQuery(Request $request)
    {
        $query = Admission::with(['programme', 'academicYear'])->latest();

        if ($request->filled('status')) {
            $query->where('admission_status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('programme_id')) {
            $query->where('programme_id', $request->programme_id);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('hall')) {
            $query->where('hall', $request->hall);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('applicant_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Delete a batch of admissions the officer selected on the queue. Only ever removes
     * un-migrated admissions - a migrated one's Student record is the record of truth
     * from that point on, and its admission history must stay available (rule from the
     * spec: "Do not lose the history of important admission changes").
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admission_ids' => 'required|array|min:1',
            'admission_ids.*' => 'integer|exists:admissions,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admissions.index')->with('error', 'Select at least one record to delete.');
        }

        $migratedCount = Admission::whereIn('id', $request->admission_ids)
            ->where('admission_status', Admission::STATUS_MIGRATED)
            ->count();

        $count = Admission::whereIn('id', $request->admission_ids)
            ->where('admission_status', '!=', Admission::STATUS_MIGRATED)
            ->delete();

        $message = "Deleted {$count} admission record(s).";
        if ($migratedCount > 0) {
            $message .= " {$migratedCount} already-migrated record(s) were skipped to preserve student history.";
        }

        return redirect()->route('admissions.index')->with('success', $message);
    }

    public function uploadForm()
    {
        return view('admissions.upload');
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admissions.import.form')->withErrors($validator)->withInput();
        }

        $import = new AdmissionImport(Auth::user());
        Excel::import($import, $request->file('excel_file'));

        $stats = $import->getStats();
        $message = "Imported {$stats['processed']} applicant(s), skipped {$stats['skipped']}. Emailed credentials to {$stats['emailed']} of them.";

        if (!empty($stats['errors'])) {
            $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 8));
            if (count($stats['errors']) > 8) {
                $message .= ' (+' . (count($stats['errors']) - 8) . ' more)';
            }

            return redirect()->route('admissions.index')->with('warning', $message);
        }

        return redirect()->route('admissions.index')->with('success', $message);
    }

    public function downloadTemplate()
    {
        return Excel::download(new AdmissionTemplateExport, 'admissions_template.xlsx');
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new AdmissionsExport($request), 'admissions.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $admissions = $this->filteredQuery($request)->get();

        $pdf = PDF::loadView('admissions.export-pdf', compact('admissions'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('admissions.pdf');
    }

    /**
     * Hall assignment summary across all admissions - counts per hall, and the
     * not-yet-assigned queue - mirroring StudentHallController's view of the Student
     * table for the pre-migration applicant population.
     */
    public function halls(Request $request)
    {
        $query = Admission::query()->whereNotIn('admission_status', [Admission::STATUS_WITHDRAWN]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('applicant_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('hall')) {
            $query->where('hall', $request->hall);
        } elseif ($request->filled('status') && $request->status === 'unassigned') {
            $query->where(function ($q) {
                $q->whereNull('hall')->orWhere('hall', '');
            });
        }

        $admissions = $query->with('programme')->orderBy('hall')->orderBy('full_name')->paginate(50)->withQueryString();

        $hallCounts = Admission::whereNotNull('hall')->where('hall', '!=', '')
            ->whereNotIn('admission_status', [Admission::STATUS_WITHDRAWN])
            ->select('hall', DB::raw('count(*) as total'))
            ->groupBy('hall')
            ->orderBy('hall')
            ->get();

        $unassignedCount = Admission::whereNotIn('admission_status', [Admission::STATUS_WITHDRAWN])
            ->where(function ($q) {
                $q->whereNull('hall')->orWhere('hall', '');
            })->count();

        return view('admissions.halls', compact('admissions', 'hallCounts', 'unassignedCount'));
    }

    public function hallsExport(Request $request)
    {
        return Excel::download(new AdmissionsExport($request), 'admissions_by_hall.xlsx');
    }

    public function create()
    {
        $programmes = Programme::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return view('admissions.create', compact('programmes', 'academicYears'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'programme_id' => 'required|exists:programmes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'level' => 'required|integer|min:100',
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admissions.create')->withErrors($validator)->withInput();
        }

        $admission = AdmissionService::import($validator->validated(), Auth::user());

        $message = "Applicant imported as {$admission->applicant_number}. Temporary password: {$admission->generated_password}";
        $message .= $admission->credentials_emailed
            ? " (also emailed to {$admission->email})."
            : ($admission->email
                ? ' Emailing the credentials failed - share the password above with the applicant directly.'
                : ' No email on file - share the password above with the applicant directly.');

        return redirect()->route('admissions.show', $admission)->with('success', $message);
    }

    public function show(Admission $admission)
    {
        $admission->load(['programme', 'academicYear', 'billItems.createdBy', 'payments', 'photoAudits.changedBy', 'auditLogs.performedBy']);
        $feeCategories = FeeCategory::options();
        $canMigrate = AdmissionEligibilityService::canMigrate($admission);
        $canProceed = AdmissionEligibilityService::canProceedToProcessing($admission);
        $halls = $this->availableHalls();

        return view('admissions.show', compact('admission', 'feeCategories', 'canMigrate', 'canProceed', 'halls'));
    }

    /**
     * The real pool of halls in use - same source AdmissionService::autoAssignHall()
     * balances against (Student.hall) plus any hall already typed onto an Admission that
     * doesn't have a Student using it yet, so a manually-assigned hall never disappears
     * from the dropdown on a later visit.
     */
    protected function availableHalls()
    {
        $studentHalls = \App\Models\Student::whereNotNull('hall')->where('hall', '!=', '')->distinct()->pluck('hall');
        $admissionHalls = Admission::whereNotNull('hall')->where('hall', '!=', '')->distinct()->pluck('hall');

        return $studentHalls->merge($admissionHalls)->unique()->sort()->values();
    }

    public function editPhoto(Admission $admission)
    {
        return view('admissions.photo', compact('admission'));
    }

    public function updatePhoto(Request $request, Admission $admission)
    {
        $request->validate([
            'passport_photo' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        AdmissionService::uploadPhoto($admission, $request->file('passport_photo'), Auth::user(), $request->reason);

        $message = 'Passport photo updated.';

        // Photo upload doubles as the admin's confirmation that this applicant is ready
        // for hall processing - auto-assign a hall right here if one isn't set yet.
        if (!$admission->hall) {
            $admission = AdmissionService::autoAssignHall($admission, Auth::user());

            if ($admission->hall) {
                $message .= " Automatically assigned to {$admission->hall}.";
            }
        }

        return redirect()->route('admissions.show', $admission)->with('success', $message);
    }

    /**
     * Stream the admission's passport photo from the protected `local` disk - never
     * publicly reachable via /storage/..., only through this permission-gated route.
     */
    public function photo(Admission $admission)
    {
        abort_unless($admission->passport_photo && Storage::disk('local')->exists($admission->passport_photo), 404);

        return Storage::disk('local')->response($admission->passport_photo);
    }

    public function withdraw(Request $request, Admission $admission)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);

        AdmissionService::withdraw($admission, Auth::user(), $request->reason);

        return redirect()->route('admissions.show', $admission)->with('success', 'Admission withdrawn.');
    }

    public function markDocumentsVerified(Admission $admission)
    {
        AdmissionService::markDocumentsVerified($admission, Auth::user());

        return redirect()->route('admissions.show', $admission)->with('success', 'Documents marked as verified.');
    }

    public function updateReferenceNumber(Request $request, Admission $admission)
    {
        $request->validate(['reference_number' => 'required|string|size:7']);

        AdmissionService::updateReferenceNumber($admission, $request->reference_number, Auth::user());

        return redirect()->route('admissions.show', $admission)->with('success', 'Reference number saved.');
    }

    public function assignHall(Request $request, Admission $admission)
    {
        $request->validate(['hall' => 'required|string|max:255']);

        AdmissionService::assignHall($admission, $request->hall, Auth::user());

        return redirect()->route('admissions.show', $admission)->with('success', 'Hall assigned.');
    }

    public function approve(Admission $admission)
    {
        AdmissionService::approve($admission, Auth::user());

        return redirect()->route('admissions.show', $admission)->with('success', 'Admission approved.');
    }

    public function migrate(Request $request, Admission $admission)
    {
        $request->validate(['reuse_photo' => 'nullable|boolean']);

        $student = AdmissionService::migrateToStudent($admission, Auth::user(), $request->boolean('reuse_photo', true));

        return redirect()->route('students.show', $student)->with('success', "Migrated to student {$student->index_number}.");
    }

    public function letter(Admission $admission)
    {
        $admission->load(['programme', 'academicYear']);

        return self::buildLetterPdf($admission);
    }

    /**
     * Institution + admission settings merged, keyed by setting key - shared by the
     * admission letter and the acceptance form, both of which need the letterhead
     * details (name, address, phone, logo, principal signature).
     */
    public static function letterSettings(): array
    {
        $settings = [];
        foreach (DB::table('settings')->where('category', 'institution')->get() as $setting) {
            $settings[$setting->key] = $setting->value;
        }
        foreach (DB::table('settings')->where('category', 'admission')->get() as $setting) {
            $settings[$setting->key] = $setting->value;
        }

        return $settings;
    }

    /**
     * Shared by both the officer-facing and applicant-facing letter downloads. One
     * template; $isFinal toggles the principal signature block once the admission has
     * been fully approved.
     */
    public static function buildLetterPdf(Admission $admission)
    {
        $settings = self::letterSettings();

        $isFinal = $admission->admission_status === Admission::STATUS_APPROVED
            || $admission->admission_status === Admission::STATUS_MIGRATED;

        // dompdf renders server-side with no session, so the protected passport photo
        // can't be linked as an authenticated URL - it has to be inlined as a data URI.
        // Falls back to a generic avatar so the photo box is never just blank/text on a
        // letter generated before the admin has uploaded a photo.
        $photoDataUri = self::photoDataUri($admission) ?? self::defaultAvatarDataUri();

        $bodyHtml = \App\Models\AdmissionLetterTemplate::bodyFor($admission, $isFinal, $settings['institution_name'] ?? null);

        $pdf = PDF::loadView('admissions.letters.admission-letter', compact('admission', 'settings', 'isFinal', 'photoDataUri', 'bodyHtml'));
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
        ]);

        return $pdf->download('admission-letter-' . $admission->applicant_number . '.pdf');
    }

    /**
     * The applicant's passport photo as a data URI, or null if none has been uploaded
     * yet - callers fall back to defaultAvatarDataUri() where a photo must always show.
     */
    public static function photoDataUri(Admission $admission): ?string
    {
        if (!$admission->passport_photo || !Storage::disk('local')->exists($admission->passport_photo)) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($admission->passport_photo);

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('local')->get($admission->passport_photo));
    }

    /**
     * Generic silhouette avatar shown in place of a passport photo until the admin
     * uploads a real one.
     */
    public static function defaultAvatarDataUri(): ?string
    {
        $path = public_path('images/default-avatar.jpg');

        if (!file_exists($path)) {
            return null;
        }

        return 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
    }

    public function acceptanceForm(Admission $admission)
    {
        $admission->load(['programme', 'academicYear']);

        return self::buildAcceptanceFormPdf($admission);
    }

    /**
     * The printable Acceptance Form an applicant fills, signs, and returns - static so
     * ApplicantController can reuse it for the applicant's own copy.
     */
    public static function buildAcceptanceFormPdf(Admission $admission)
    {
        $settings = self::letterSettings();

        $pdf = PDF::loadView('admissions.letters.acceptance-form', compact('admission', 'settings'));
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
        ]);

        return $pdf->download('acceptance-form-' . $admission->applicant_number . '.pdf');
    }
}
