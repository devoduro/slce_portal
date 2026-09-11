<?php

namespace App\Http\Controllers;

use App\Exports\AdmissionBillTemplateExport;
use App\Exports\AdmissionPaymentTemplateExport;
use App\Imports\AdmissionBillImport;
use App\Imports\AdmissionPaymentImport;
use App\Models\AcademicYear;
use App\Models\Admission;
use App\Models\AdmissionBillItem;
use App\Models\AdmissionPayment;
use App\Models\FeeCategory;
use App\Models\Programme;
use App\Services\AdmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class AdmissionBillingController extends Controller
{
    /**
     * Accounts' admission billing queue: awaiting billing, billed, outstanding,
     * awaiting verification, and confirmed.
     */
    public function index(Request $request)
    {
        $query = Admission::with(['programme', 'academicYear'])
            ->whereNotIn('admission_status', [Admission::STATUS_WITHDRAWN])
            ->latest();

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('applicant_number', 'like', "%{$search}%");
            });
        }

        $admissions = $query->paginate(20)->withQueryString();

        return view('admission-billing.index', compact('admissions'));
    }

    public function show(Admission $admission)
    {
        $admission->load(['programme', 'academicYear', 'billItems.createdBy', 'payments.recordedBy', 'payments.verifiedBy', 'payments.confirmedBy']);
        $feeCategories = FeeCategory::options();

        return view('admission-billing.show', compact('admission', 'feeCategories'));
    }

    public function storeBillItem(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'category' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_deadline' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        AdmissionService::addBillItem($admission, $data, Auth::user());

        return redirect()->route('admission-billing.show', $admission)->with('success', 'Bill item added.');
    }

    public function destroyBillItem(AdmissionBillItem $billItem)
    {
        $admission = $billItem->admission;
        AdmissionService::removeBillItem($billItem, Auth::user());

        return redirect()->route('admission-billing.show', $admission)->with('success', 'Bill item removed.');
    }

    public function recordPayment(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:255',
            'bank' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
            'evidence' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:4096',
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($request->hasFile('evidence')) {
            $data['evidence_path'] = $request->file('evidence')->store('admission-payment-evidence', 'local');
        }
        unset($data['evidence']);

        AdmissionService::recordPayment($admission, $data, Auth::user());

        return redirect()->route('admission-billing.show', $admission)->with('success', 'Payment recorded.');
    }

    public function verifyPayment(AdmissionPayment $payment)
    {
        AdmissionService::verifyPayment($payment, Auth::user());

        return redirect()->route('admission-billing.show', $payment->admission)->with('success', 'Payment verified.');
    }

    public function confirmPayment(AdmissionPayment $payment)
    {
        AdmissionService::confirmPayment($payment, Auth::user());

        return redirect()->route('admission-billing.show', $payment->admission)->with('success', 'Payment confirmed. The applicant is now unlocked to proceed.');
    }

    public function rejectPayment(Request $request, AdmissionPayment $payment)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        AdmissionService::rejectPayment($payment, Auth::user(), $request->reason);

        return redirect()->route('admission-billing.show', $payment->admission)->with('success', 'Payment rejected.');
    }

    public function reversePayment(Request $request, AdmissionPayment $payment)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        AdmissionService::reversePayment($payment, Auth::user(), $request->reason);

        return redirect()->route('admission-billing.show', $payment->admission)->with('success', 'Payment reversed.');
    }

    /**
     * One bill applied to every admission in a programme/academic year at once (a
     * blanket per-programme fee) - as opposed to billUploadForm()'s per-applicant
     * spreadsheet, where each row can carry a different amount.
     */
    public function bulkBillForm()
    {
        $programmes = Programme::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $feeCategories = FeeCategory::options();

        return view('admission-billing.bulk-bill', compact('programmes', 'academicYears', 'feeCategories'));
    }

    protected function validateBulkBill(Request $request)
    {
        return $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'nullable|integer|min:100',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.payment_deadline' => 'nullable|date',
        ]);
    }

    public function bulkBillPreview(Request $request)
    {
        $data = $this->validateBulkBill($request);

        $admissions = Admission::with('programme')
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('programme_id', $data['programme_id'])
            ->when($data['level'] ?? null, fn ($query) => $query->where('level', $data['level']))
            ->whereNotIn('admission_status', [Admission::STATUS_WITHDRAWN, Admission::STATUS_MIGRATED])
            ->orderBy('full_name')
            ->get();

        $academicYear = AcademicYear::findOrFail($data['academic_year_id']);
        $programme = Programme::findOrFail($data['programme_id']);
        $totalPerAdmission = array_sum(array_column($data['items'], 'amount'));

        return view('admission-billing.bulk-bill-preview', compact('admissions', 'academicYear', 'programme', 'data', 'totalPerAdmission'));
    }

    public function bulkBillStore(Request $request)
    {
        $data = $this->validateBulkBill($request);

        $items = array_map(fn (array $item) => [
            'category' => $item['category'],
            'description' => $item['description'] ?? null,
            'amount' => (float) $item['amount'],
            'payment_deadline' => $item['payment_deadline'] ?? null,
            'notes' => 'Bulk billed by programme',
        ], $data['items']);

        $count = AdmissionService::bulkBillByProgramme(
            (int) $data['academic_year_id'],
            (int) $data['programme_id'],
            $data['level'] ? (int) $data['level'] : null,
            $items,
            Auth::user()
        );

        return redirect()->route('admission-billing.index')->with('success', "Billed {$count} admission(s) with " . count($items) . ' fee item(s) each.');
    }

    public function billUploadForm()
    {
        return view('admission-billing.bill-upload');
    }

    public function billImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admission-billing.bill-upload.form')->withErrors($validator)->withInput();
        }

        $import = new AdmissionBillImport(Auth::user());
        Excel::import($import, $request->file('excel_file'));

        $stats = $import->getStats();
        $message = "Billed {$stats['processed']} item(s), skipped {$stats['skipped']}.";

        if (!empty($stats['errors'])) {
            $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 8));
            if (count($stats['errors']) > 8) {
                $message .= ' (+' . (count($stats['errors']) - 8) . ' more)';
            }

            return redirect()->route('admission-billing.index')->with('warning', $message);
        }

        return redirect()->route('admission-billing.index')->with('success', $message);
    }

    public function billTemplate()
    {
        return Excel::download(new AdmissionBillTemplateExport, 'admission_bill_template.xlsx');
    }

    public function paymentUploadForm()
    {
        return view('admission-billing.payment-upload');
    }

    public function paymentImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admission-billing.payment-upload.form')->withErrors($validator)->withInput();
        }

        $import = new AdmissionPaymentImport(Auth::user());
        Excel::import($import, $request->file('excel_file'));

        $stats = $import->getStats();
        $message = "Recorded {$stats['processed']} payment(s), skipped {$stats['skipped']}. Each still needs to be Verified and Confirmed individually.";

        if (!empty($stats['errors'])) {
            $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 8));
            if (count($stats['errors']) > 8) {
                $message .= ' (+' . (count($stats['errors']) - 8) . ' more)';
            }

            return redirect()->route('admission-billing.index')->with('warning', $message);
        }

        return redirect()->route('admission-billing.index')->with('success', $message);
    }

    public function paymentTemplate()
    {
        return Excel::download(new AdmissionPaymentTemplateExport, 'admission_payment_template.xlsx');
    }
}
