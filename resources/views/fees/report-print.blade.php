<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fees Report — {{ $academicYear->name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            table { font-size: 9px !important; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="no-print bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center sticky top-0 z-20 shadow-sm">
        <a href="{{ route('fees.report', ['academic_year_id' => $academicYear->id]) }}" class="text-sm text-gray-600 hover:text-primary-600">
            <i class="fas fa-arrow-left mr-1"></i> Back to Fees Report
        </a>
        <button onclick="window.print()" class="bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
            <i class="fas fa-print mr-1"></i> Print
        </button>
    </div>

    <div class="max-w-5xl mx-auto p-8 bg-white my-6 print:my-0 print:shadow-none shadow-md rounded-lg">
        <!-- Letterhead -->
        <div class="text-center border-b-2 border-gray-800 pb-4 mb-6">
            @php
                $logoFile = public_path('images/logos/institution_logo.png');
                $logoBase64 = file_exists($logoFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile)) : null;
            @endphp
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Institution Logo" class="mx-auto mb-2" style="max-width: 90px; max-height: 90px;">
            @endif
            <h1 class="text-xl font-bold uppercase tracking-wide">{{ $settings['institution_name'] ?? 'Institution' }}</h1>
            @if(!empty($settings['institution_address']))
                <p class="text-xs text-gray-500 mt-1">{{ $settings['institution_address'] }}</p>
            @endif
            <h2 class="text-lg font-semibold text-primary-700 mt-3">Student Financial Report</h2>
            <p class="text-sm text-gray-600 mt-1">Academic Year: <span class="font-semibold">{{ $academicYear->name }}</span></p>
        </div>

        <!-- Grand Totals -->
        <div class="grid grid-cols-3 gap-4 mb-6 text-center">
            <div class="border border-gray-300 rounded-lg p-3">
                <p class="text-xs text-gray-500 uppercase">Total Expected</p>
                <p class="text-lg font-semibold">{{ number_format($grandExpected, 2) }}</p>
            </div>
            <div class="border border-gray-300 rounded-lg p-3">
                <p class="text-xs text-gray-500 uppercase">Total Collected</p>
                <p class="text-lg font-semibold text-green-700">{{ number_format($grandCollected, 2) }}</p>
            </div>
            <div class="border border-gray-300 rounded-lg p-3">
                <p class="text-xs text-gray-500 uppercase">Outstanding Balance</p>
                <p class="text-lg font-semibold text-red-700">{{ number_format($grandExpected - $grandCollected, 2) }}</p>
            </div>
        </div>

        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-3 py-2 text-left">Programme</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Level</th>
                    <th class="border border-gray-300 px-3 py-2 text-right">Students</th>
                    <th class="border border-gray-300 px-3 py-2 text-right">Fee Amount</th>
                    <th class="border border-gray-300 px-3 py-2 text-right">Expected</th>
                    <th class="border border-gray-300 px-3 py-2 text-right">Collected</th>
                    <th class="border border-gray-300 px-3 py-2 text-right">Balance</th>
                    <th class="border border-gray-300 px-3 py-2 text-right">% Collected</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="border border-gray-300 px-3 py-2">{{ $row['programme'] }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $row['level'] }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ $row['students'] }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ $row['fee_amount'] !== null ? number_format($row['fee_amount'], 2) : 'Not set' }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ number_format($row['expected'], 2) }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ number_format($row['collected'], 2) }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ number_format($row['balance'], 2) }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ $row['percentage'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="border border-gray-300 px-3 py-6 text-center text-gray-400">No students found for this academic year.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
                <tfoot>
                    <tr class="bg-gray-50 font-semibold">
                        <td class="border border-gray-300 px-3 py-2" colspan="2">Grand Total</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ $rows->sum('students') }}</td>
                        <td class="border border-gray-300 px-3 py-2"></td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ number_format($grandExpected, 2) }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ number_format($grandCollected, 2) }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ number_format($grandExpected - $grandCollected, 2) }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ $grandExpected > 0 ? round(($grandCollected / $grandExpected) * 100, 1) : 0 }}%</td>
                    </tr>
                </tfoot>
            @endif
        </table>

        <p class="text-xs text-gray-400 text-center mt-8">Printed {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
