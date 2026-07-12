<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Course Registration Slip — {{ $student->full_name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8',
                            500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e',
                        },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }

            @page {
                size: A4 portrait;
                margin: 12mm;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="no-print bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center sticky top-0 z-20 shadow-sm">
        <a href="{{ route('student.registration.index') }}" class="text-sm text-gray-600 hover:text-primary-600">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
        <button onclick="window.print()" class="bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
            <i class="fas fa-print mr-1"></i> Print
        </button>
    </div>

    <div class="max-w-3xl mx-auto p-8 print:p-0 print:max-w-none bg-white my-6 print:my-0 print:shadow-none shadow-md rounded-lg">
        <!-- Letterhead -->
        <div class="text-center border-b-2 border-gray-800 pb-4 mb-6 relative">
            @php
                $logoFile = public_path('images/logos/institution_logo.png');
                $logoBase64 = file_exists($logoFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile)) : null;
            @endphp
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Institution Logo" class="mx-auto mb-2 max-w-[90px] max-h-[90px]">
            @endif
            <h1 class="text-xl font-bold uppercase tracking-wide">{{ $settings['institution_name'] ?? 'Institution' }}</h1>
            @if(!empty($settings['institution_address']))
                <p class="text-xs text-gray-500 mt-1">{{ $settings['institution_address'] }}</p>
            @endif
            <h2 class="text-lg font-semibold text-primary-700 mt-3">
                Course Registration Slip
            </h2>
            <p class="text-sm text-gray-600 mt-1">{{ $semester->name ?? 'N/A' }} &bull; {{ $semester->academicYear->name ?? '' }}</p>
        </div>

        <!-- Student Details -->
        <div class="relative grid grid-cols-2 gap-4 mb-6 text-sm">
            <x-student-photo :student="$student" class="absolute top-0 right-0 w-20 h-20 rounded-lg border border-gray-200" />
            <div>
                <p class="text-gray-500">Full Name</p>
                <p class="font-semibold text-gray-900">{{ $student->full_name }}</p>
            </div>
            <div>
                <p class="text-gray-500">Index Number</p>
                <p class="font-semibold text-gray-900">{{ $student->index_number }}</p>
            </div>
            <div>
                <p class="text-gray-500">Programme</p>
                <p class="font-semibold text-gray-900">{{ $student->programme->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Level</p>
                <p class="font-semibold text-gray-900">{{ $student->level ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Class</p>
                <p class="font-semibold text-gray-900">{{ $student->classGroup->name ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Courses -->
        <table class="w-full text-sm border-collapse mb-4">
            <thead>
                <tr class="bg-gray-50">
                    <th class="border border-gray-200 px-3 py-2 text-left">Code</th>
                    <th class="border border-gray-200 px-3 py-2 text-left">Title</th>
                    <th class="border border-gray-200 px-3 py-2 text-right">Credit Hours</th>
                    <th class="border border-gray-200 px-3 py-2 text-left">Type</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                    <tr>
                        <td class="border border-gray-200 px-3 py-2">{{ $course->code }}</td>
                        <td class="border border-gray-200 px-3 py-2">{{ $course->title }}</td>
                        <td class="border border-gray-200 px-3 py-2 text-right">{{ $course->credit_hours }}</td>
                        <td class="border border-gray-200 px-3 py-2">{{ $course->is_core ? 'Core' : 'Elective' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="border border-gray-200 px-3 py-6 text-center text-gray-400">No courses selected.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="flex justify-end gap-6 text-sm mb-8">
            <p><span class="text-gray-500">Total Courses:</span> <span class="font-semibold">{{ $courses->count() }}</span></p>
            <p><span class="text-gray-500">Total Credit Hours:</span> <span class="font-semibold">{{ rtrim(rtrim(number_format($courses->sum('credit_hours'), 2), '0'), '.') }}</span></p>
        </div>

        <p class="text-xs text-gray-400 text-center mt-8">Printed {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
