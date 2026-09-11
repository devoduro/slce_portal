<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AdmissionBillItem;
use App\Models\AdmissionPayment;
use App\Models\FeeCategory;
use App\Services\AdmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
