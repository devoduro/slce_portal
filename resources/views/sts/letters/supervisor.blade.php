<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supervisor Letter — {{ $lecturer->name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="no-print bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center sticky top-0 z-20 shadow-sm">
        <a href="{{ route('sts-supervision.index') }}" class="text-sm text-gray-600 hover:text-primary-600">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
        <button onclick="window.print()" class="bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
            <i class="fas fa-print mr-1"></i> Print
        </button>
    </div>

    <div class="max-w-3xl mx-auto p-10 bg-white my-6 print:my-0 print:shadow-none shadow-md rounded-lg">
        <!-- Letterhead -->
        <div class="text-center border-b-2 border-gray-800 pb-4 mb-8">
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
            <h2 class="text-lg font-semibold text-primary-700 mt-3">STS/Internship — Supervisor Assignment Letter</h2>
        </div>

        <p class="text-sm text-gray-500 mb-6">{{ now()->format('F j, Y') }}</p>

        <p class="mb-4 leading-relaxed">
            This letter confirms that <span class="font-semibold">{{ $lecturer->name }}</span> has been assigned as the
            supervisor for the following student(s)
            @if($term)
                during the {{ $term->name }} ({{ $term->proposed_start_date->format('M d, Y') }} &ndash; {{ $term->proposed_end_date->format('M d, Y') }}):
            @else
                :
            @endif
        </p>

        <table class="w-full text-sm border-collapse mb-8">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-3 py-2 text-left w-12">#</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Index Number</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Full Name</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Type</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Partner School</th>
                </tr>
            </thead>
            <tbody>
                @forelse($placements as $index => $placement)
                    <tr>
                        <td class="border border-gray-300 px-3 py-2">{{ $index + 1 }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $placement->student->index_number ?? '' }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $placement->student->full_name ?? '' }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ ucfirst($placement->type) }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $placement->partnerSchool->name ?? 'Not selected yet' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="border border-gray-300 px-3 py-6 text-center text-gray-400">No students assigned.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p class="text-xs text-gray-400 text-center mt-10">Printed {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
