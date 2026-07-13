<?php

namespace App\Http\Controllers;

use App\Exports\FeePaymentTemplateExport;
use App\Imports\FeePaymentImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PaymentUploadController extends Controller
{
    /**
     * Show the bulk fee-payment upload form.
     */
    public function uploadForm()
    {
        return view('fees.payments.upload');
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
}
