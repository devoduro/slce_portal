<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Incomplete List</title>

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
        <a href="{{ route('results.incomplete-list', request()->query()) }}" class="text-sm text-gray-600 hover:text-primary-600">
            <i class="fas fa-arrow-left mr-1"></i> Back to Incomplete List
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
            <h2 class="text-lg font-semibold text-primary-700 mt-3">Incomplete List</h2>
            <p class="text-sm text-gray-600 mt-1">
                Students with an Incomplete (IC) result
                @if(request('academic_year_id')) &bull; Academic Year: <span class="font-semibold">{{ \App\Models\AcademicYear::find(request('academic_year_id'))?->name }}</span> @endif
                @if(request('semester_id')) &bull; Semester: <span class="font-semibold">{{ \App\Models\Semester::find(request('semester_id'))?->name }}</span> @endif
                @if(request('course_id')) &bull; Course: <span class="font-semibold">{{ \App\Models\Course::find(request('course_id'))?->code }}</span> @endif
                @if(request('programme_id')) &bull; Programme: <span class="font-semibold">{{ \App\Models\Programme::find(request('programme_id'))?->name }}</span> @endif
            </p>
        </div>

        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-3 py-2 text-left">#</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Index Number</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Student Name</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Programme</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Course</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Academic Year</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Semester</th>
                    <th class="border border-gray-300 px-3 py-2 text-center">Grade</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $index => $result)
                    <tr>
                        <td class="border border-gray-300 px-3 py-2">{{ $index + 1 }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $result->student->index_number }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $result->student->full_name }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $result->student->programme->name ?? '-' }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $result->course->code }} - {{ $result->course->title }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $result->academicYear->name }}</td>
                        <td class="border border-gray-300 px-3 py-2">{{ $result->semester->name }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-center font-semibold">{{ $result->grade }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="border border-gray-300 px-3 py-6 text-center text-gray-400">
                            No students currently have an incomplete result.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p class="text-xs text-gray-400 text-center mt-8">Printed {{ now()->format('F j, Y \a\t g:i A') }} &bull; Total: {{ $results->count() }}</p>
    </div>
</body>
</html>
