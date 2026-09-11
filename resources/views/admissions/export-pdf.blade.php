<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admissions</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .text-right { text-align: right; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { font-size: 14px; margin: 0; }
        .header p { font-size: 9px; color: #666; margin: 3px 0; }
        @page { margin: 12mm; size: A4 landscape; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admissions List</h1>
        <p>Generated {{ now()->format('F j, Y g:i A') }} &middot; {{ $admissions->count() }} record(s)</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Applicant No.</th>
                <th>Reference No.</th>
                <th>Name</th>
                <th>Programme</th>
                <th>Level</th>
                <th>Admission Status</th>
                <th>Payment Status</th>
                <th class="text-right">Billed</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Outstanding</th>
            </tr>
        </thead>
        <tbody>
            @foreach($admissions as $admission)
                <tr>
                    <td>{{ $admission->applicant_number }}</td>
                    <td>{{ $admission->reference_number }}</td>
                    <td>{{ $admission->full_name }}</td>
                    <td>{{ $admission->programme->name ?? '' }}</td>
                    <td>{{ $admission->level }}</td>
                    <td>{{ ucfirst($admission->admission_status) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $admission->payment_status)) }}</td>
                    <td class="text-right">{{ number_format($admission->totalBilled(), 2) }}</td>
                    <td class="text-right">{{ number_format($admission->totalPaid(), 2) }}</td>
                    <td class="text-right">{{ number_format($admission->outstandingBalance(), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
