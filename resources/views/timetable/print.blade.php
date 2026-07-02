<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Timetable @if($lecturer) — {{ $lecturer->name }} @elseif($classGroup) — {{ $classGroup->name }} @elseif($department) — {{ $department->name }} @endif</title>

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
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="no-print bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center sticky top-0 z-20 shadow-sm">
        <a href="{{ route('timetable.index') }}" class="text-sm text-gray-600 hover:text-primary-600">
            <i class="fas fa-arrow-left mr-1"></i> Back to Timetable
        </a>
        <button onclick="window.print()" class="bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
            <i class="fas fa-print mr-1"></i> Print
        </button>
    </div>

    <div class="max-w-6xl mx-auto p-8 bg-white my-6 print:my-0 print:shadow-none shadow-md rounded-lg">
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
            <h2 class="text-lg font-semibold text-primary-700 mt-3">Class Timetable</h2>
            <p class="text-sm text-gray-600 mt-1">
                @if($lecturer)
                    Lecturer: <span class="font-semibold">{{ $lecturer->name }}</span>
                @elseif($classGroup)
                    Class: <span class="font-semibold">{{ $classGroup->name }}</span>
                @elseif($department)
                    Department: <span class="font-semibold">{{ $department->name }}</span>
                @endif
                &bull; {{ $semester->name }} &bull; {{ $semester->academicYear->name ?? '' }}
            </p>
        </div>

        @include('timetable._grid', ['entries' => $entries, 'slotLabels' => $slotLabels, 'showActions' => false])

        <p class="text-xs text-gray-400 text-center mt-8">Printed {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
