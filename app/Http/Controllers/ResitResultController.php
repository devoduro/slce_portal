<?php

namespace App\Http\Controllers;

use App\Exports\ResitResultTemplateExport;
use App\Imports\ResitResultImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ResitResultController extends Controller
{
    /**
     * Show the resit results upload form.
     */
    public function uploadForm()
    {
        return view('results.resit_upload');
    }

    /**
     * Handle the bulk upload of resit results.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('results.resit.upload')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new ResitResultImport();
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} result(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('results.index')->with('warning', $message);
            }

            return redirect()->route('results.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('results.resit.upload')
                ->withErrors(['excel_file' => 'Error processing file: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Download the resit results upload template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new ResitResultTemplateExport, 'resit_results_template.xlsx');
    }
}
