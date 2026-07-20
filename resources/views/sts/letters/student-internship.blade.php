<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Internship Placement Letter — {{ $student->full_name }}</title>

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
        <a href="{{ route('student.sts.index') }}" class="text-sm text-gray-600 hover:text-primary-600">
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
            <h2 class="text-lg font-semibold text-primary-700 mt-3">Internship — Placement Letter</h2>
        </div>

        <p class="text-sm text-gray-500 mb-6">{{ now()->format('F j, Y') }}</p>

        <p class="mb-4">The Headteacher,</p>
        <p class="mb-6">{{ $placement->partnerSchool->name ?? '' }}<br>{{ $placement->partnerSchool->location ?? '' }}</p>

        <p class="mb-4 font-semibold">Dear Sir/Madam,</p>
        <p class="mb-4 font-semibold">RE: PLACEMENT OF FINAL-YEAR STUDENT FOR TEACHING INTERNSHIP</p>

        <div class="flex gap-6 items-start mb-4">
            <x-student-photo :student="$student" class="w-24 h-28 object-cover rounded border border-gray-300 flex-shrink-0" />
            <p class="leading-relaxed">
                This letter introduces <span class="font-semibold">{{ $student->full_name }}</span>
                (Index Number: <span class="font-semibold">{{ $student->index_number }}</span>),
                a final-year (Level {{ $placement->level }}) student of {{ $student->programme->name ?? '' }} at
                {{ $settings['institution_name'] ?? 'this institution' }}, who has been placed at your school for
                the Teaching Internship during the {{ $term->name }}
                ({{ $term->proposed_start_date->format('M d, Y') }} &ndash; {{ $term->proposed_end_date->format('M d, Y') }}).
            </p>
        </div>

        <p class="mb-4 leading-relaxed">
            As a final-year intern, the student is expected to take full responsibility for planning, teaching, and
            assessment in the classes assigned, working independently under your school's general oversight rather
            than close, continuous supervision. This Internship forms part of the formal assessment of the
            student's readiness to practise as a qualified teacher.
        </p>

        <p class="mb-4 leading-relaxed">
            The assigned supervisor{{ $placement->secondLecturer ? 's are' : ' for this placement is' }} <span class="font-semibold">{{ $placement->lecturer->name ?? '' }}</span>{{ $placement->secondLecturer ? ' and ' . $placement->secondLecturer->name : '' }}
            and will visit periodically to observe and evaluate the student's performance. We would be grateful if
            you could grant the student full teaching responsibilities appropriate to their training, and share any
            concerns about their conduct or performance with the supervisor promptly.
        </p>

        <p class="mb-10 leading-relaxed">Thank you for your cooperation.</p>

        <div class="flex justify-between items-end mb-12">
            <div>
                @if(!empty($stsSettings['sts_coordinator_signature']))
                    <img src="{{ asset('storage/' . $stsSettings['sts_coordinator_signature']) }}" alt="Signature" class="max-h-16 mb-1">
                @endif
                <p class="border-t border-gray-800 pt-1 text-sm font-semibold">{{ $stsSettings['sts_coordinator_name'] ?? 'STS Coordinator' }}</p>
                <p class="text-xs text-gray-500">STS Coordinator</p>
            </div>
        </div>

        <div class="border-t-2 border-dashed border-gray-400 pt-6">
            <p class="text-sm font-semibold mb-6">To be completed by the Headteacher:</p>
            <div class="grid grid-cols-2 gap-8 text-sm">
                <div>
                    <p class="border-t border-gray-800 pt-1 mt-12">Headteacher's Signature</p>
                </div>
                <div>
                    <p class="border-t border-gray-800 pt-1 mt-12">School Stamp</p>
                </div>
            </div>
        </div>

        <p class="text-xs text-gray-400 text-center mt-10">Printed {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
