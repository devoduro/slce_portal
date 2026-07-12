<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statement of Account — {{ $student->full_name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            table { font-size: 10px !important; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="no-print bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center sticky top-0 z-20 shadow-sm">
        <a href="{{ route('fees.show', $student) }}" class="text-sm text-gray-600 hover:text-primary-600">
            <i class="fas fa-arrow-left mr-1"></i> Back to Fee Ledger
        </a>
        <button onclick="window.print()" class="bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
            <i class="fas fa-print mr-1"></i> Print
        </button>
    </div>

    <div class="max-w-4xl mx-auto p-8 bg-white my-6 print:my-0 print:shadow-none shadow-md rounded-lg">
        <!-- Letterhead -->
        <div class="relative text-center border-b-2 border-gray-800 pb-4 mb-6">
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
            <h2 class="text-lg font-semibold text-primary-700 mt-3">Statement of Account</h2>

            <x-student-photo :student="$student" class="absolute top-0 right-0 w-20 h-20 rounded-lg border border-gray-200" />
        </div>

        <!-- Student Details -->
        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div>
                <p class="text-gray-500">Student Name</p>
                <p class="font-semibold">{{ $student->full_name }}</p>
            </div>
            <div>
                <p class="text-gray-500">Index Number</p>
                <p class="font-semibold">{{ $student->index_number }}</p>
            </div>
            <div>
                <p class="text-gray-500">Programme</p>
                <p class="font-semibold">{{ $student->programme->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Level</p>
                <p class="font-semibold">{{ $student->level ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Balance Summary -->
        <div class="mb-6 text-center">
            <div class="border border-gray-300 rounded-lg p-3 inline-block min-w-[240px]">
                <p class="text-xs text-gray-500 uppercase">Total Balance Due</p>
                <p class="text-lg font-semibold {{ $balanceDue > 0 ? 'text-red-700' : 'text-green-700' }}">{{ number_format($balanceDue, 2) }}</p>
            </div>
        </div>

        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-3 py-2 text-left">Date</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Academic Year</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Description</th>
                    <th class="border border-gray-300 px-3 py-2 text-right">Debit</th>
                    <th class="border border-gray-300 px-3 py-2 text-right">Credit</th>
                    <th class="border border-gray-300 px-3 py-2 text-right">Balance</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Bank</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Payment Mode</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledger as $row)
                    <tr>
                        <td class="border border-gray-300 px-3 py-2">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $row['academic_year'] }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $row['description'] }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ $row['debit'] ? number_format($row['debit'], 2) : '-' }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ $row['credit'] ? number_format($row['credit'], 2) : '-' }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right font-medium">{{ number_format($row['balance'], 2) }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $row['bank'] ?? '-' }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $row['payment_mode'] ? ucwords(str_replace('_', ' ', $row['payment_mode'])) : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="border border-gray-300 px-3 py-6 text-center text-gray-400">No fee transactions recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p class="text-xs text-gray-400 text-center mt-8">Printed {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
