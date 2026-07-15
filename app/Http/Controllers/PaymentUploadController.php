<?php

namespace App\Http\Controllers;

use App\Exports\FeePaymentTemplateExport;
use App\Imports\FeePaymentImport;
use App\Models\StudentPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PaymentUploadController extends Controller
{
    /**
     * Show the bulk fee-payment upload form, alongside a paginated, searchable list of
     * recorded payments (most recent first) so a bad upload can be found and deleted.
     */
    public function uploadForm(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [20, 50, 100, 200, 500], true)) {
            $perPage = 20;
        }

        $query = StudentPayment::with(['student', 'academicYear'])->latest('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('index_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $payments = $query->paginate($perPage)->withQueryString();

        return view('fees.payments.upload', compact('payments'));
    }

    /**
     * Handle the bulk upload of fee payments, keyed by index number.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('fees.payments.upload')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new FeePaymentImport();
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} payment(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('fees.index')->with('warning', $message);
            }

            return redirect()->route('fees.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('fees.payments.upload')
                ->with('error', 'Error importing payments: ' . $e->getMessage());
        }
    }

    /**
     * Download the fee payment upload template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new FeePaymentTemplateExport, 'fee_payments_template.xlsx');
    }

    /**
     * Delete multiple payment records at once (e.g. to clean up a bad bulk upload).
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'payment_ids' => 'required|array',
            'payment_ids.*' => 'exists:student_payments,id',
        ]);

        $count = StudentPayment::whereIn('id', $request->payment_ids)->delete();

        return back()->with('success', "Deleted {$count} payment(s).");
    }
}
