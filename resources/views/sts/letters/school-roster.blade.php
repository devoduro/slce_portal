<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTitle }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @media print {
            .no-print { display: none !important; }
            .school-section { page-break-inside: avoid; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="no-print bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center sticky top-0 z-20 shadow-sm">
        <a href="{{ route('partner-schools.index') }}" class="text-sm text-gray-600 hover:text-primary-600">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
        <button onclick="window.print()" class="bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
            <i class="fas fa-print mr-1"></i> Print
        </button>
    </div>

    <div class="max-w-4xl mx-auto p-10 bg-white my-6 print:my-0 print:shadow-none shadow-md rounded-lg">
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
            <h2 class="text-lg font-semibold text-primary-700 mt-3">{{ $reportTitle }}</h2>
            @if($term)
                <p class="text-sm text-gray-500 mt-1">{{ $term->name }} &bull; {{ $term->proposed_start_date->format('M d, Y') }} &ndash; {{ $term->proposed_end_date->format('M d, Y') }}</p>
            @endif
        </div>

        @if($eligibilitySummary)
            <div class="grid grid-cols-3 gap-4 mb-8 text-center">
                <div class="border border-gray-300 rounded-lg py-3">
                    <div class="text-2xl font-bold text-gray-900">{{ $eligibilitySummary['eligible'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">Eligible Students</div>
                </div>
                <div class="border border-gray-300 rounded-lg py-3">
                    <div class="text-2xl font-bold text-green-700">{{ $eligibilitySummary['selected'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">Selected a School</div>
                </div>
                <div class="border border-gray-300 rounded-lg py-3">
                    <div class="text-2xl font-bold text-red-600">{{ $eligibilitySummary['unplaced'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">Not Yet Placed</div>
                </div>
            </div>
        @endif

        @if(!$term)
            <p class="text-center text-gray-400 py-8">No active STS term - no placements to list.</p>
        @elseif($schools->isEmpty())
            <p class="text-center text-gray-400 py-8">No partner schools match this filter.</p>
        @else
            @foreach($schools as $school)
                @php $roster = $placementsBySchool->get($school->id, collect())->sortBy('student.full_name'); @endphp
                <div class="school-section mb-10">
                    <div class="flex items-center justify-between mb-2 border-b border-gray-300 pb-2">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">{{ $school->name }}</h3>
                            <p class="text-xs text-gray-500">
                                {{ $school->categoryLabel() }}
                                @if($school->typeLabel()) &bull; {{ $school->typeLabel() }} @endif
                                @if($school->location) &bull; {{ $school->location }} @endif
                            </p>
                        </div>
                        <span class="text-xs font-medium text-gray-500">{{ $roster->count() }} student{{ $roster->count() === 1 ? '' : 's' }}</span>
                    </div>

                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-3 py-2 text-left w-10">#</th>
                                <th class="border border-gray-300 px-3 py-2 text-left">Index Number</th>
                                <th class="border border-gray-300 px-3 py-2 text-left">Full Name</th>
                                <th class="border border-gray-300 px-3 py-2 text-left">Level</th>
                                <th class="border border-gray-300 px-3 py-2 text-left">Type</th>
                                <th class="border border-gray-300 px-3 py-2 text-left">Supervisor(s)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roster as $index => $placement)
                                <tr>
                                    <td class="border border-gray-300 px-3 py-2">{{ $index + 1 }}</td>
                                    <td class="border border-gray-300 px-3 py-2">{{ $placement->student->index_number ?? '' }}</td>
                                    <td class="border border-gray-300 px-3 py-2">{{ $placement->student->full_name ?? '' }}</td>
                                    <td class="border border-gray-300 px-3 py-2">{{ $placement->level }}</td>
                                    <td class="border border-gray-300 px-3 py-2">{{ ucfirst($placement->type) }}</td>
                                    <td class="border border-gray-300 px-3 py-2">
                                        {{ $placement->lecturer->name ?? 'Not assigned' }}{{ $placement->secondLecturer ? ', ' . $placement->secondLecturer->name : '' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="border border-gray-300 px-3 py-4 text-center text-gray-400">No students have selected this school yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endif

        <p class="text-xs text-gray-400 text-center mt-10">Printed {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
