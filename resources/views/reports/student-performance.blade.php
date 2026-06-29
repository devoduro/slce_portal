<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Student Performance Report') }}
            </h2>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Back to Reports
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('reports.student-performance') }}" method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="academic_year_id" class="block text-sm font-medium text-gray-700">Academic Year</label>
                                <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                    @foreach($academicYears ?? [] as $year)
                                        <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="programme_id" class="block text-sm font-medium text-gray-700">Programme</label>
                                <select id="programme_id" name="programme_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                    @foreach($programmes ?? [] as $prog)
                                        <option value="{{ $prog->id }}" {{ request('programme_id') == $prog->id ? 'selected' : '' }}>
                                            {{ $prog->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700">
                                Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if(isset($studentPerformance))
                <!-- Report Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Student Performance Report</h3>
                        <p class="text-gray-600">
                            Academic Year: <strong>{{ $academicYear->name }}</strong> | 
                            Programme: <strong>{{ $programme->name }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Top Performers -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Top Performers</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach(array_slice($studentPerformance, 0, 3) as $index => $student)
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <div class="flex items-center mb-2">
                                        <div class="rounded-full p-2 {{ $index === 0 ? 'bg-yellow-100 text-yellow-800' : ($index === 1 ? 'bg-gray-100 text-gray-800' : 'bg-amber-100 text-amber-800') }} mr-3">
                                            <i class="fas fa-trophy"></i>
                                        </div>
                                        <h4 class="font-medium">{{ $index + 1 }}. {{ $student['student']->full_name }}</h4>
                                    </div>
                                    <p class="text-sm text-gray-600">Index Number: {{ $student['student']->index_number }}</p>
                                    <div class="mt-2 flex justify-between">
                                        <span class="text-sm text-gray-600">GPA:</span>
                                        <span class="font-bold">{{ number_format($student['gpa'], 2) }}</span>
                                    </div>
                                    <div class="mt-1 flex justify-between">
                                        <span class="text-sm text-gray-600">Courses Passed:</span>
                                        <span>{{ $student['passed_courses'] }}/{{ $student['total_courses'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Performance Distribution Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">GPA Distribution</h3>
                        <div class="w-full h-80">
                            <canvas id="gpaDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Student Performance Data</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Rank
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Student Name
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Index Number
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total Courses
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Passed
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Failed
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            GPA
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($studentPerformance as $index => $student)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $student['student']->full_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $student['student']->index_number }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $student['total_courses'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $student['passed_courses'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $student['failed_courses'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $student['gpa'] >= 3.5 ? 'text-green-600' : ($student['gpa'] >= 2.0 ? 'text-blue-600' : 'text-red-600') }}">
                                                {{ number_format($student['gpa'], 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <a href="{{ route('students.show', $student['student']->id) }}" class="text-primary-600 hover:text-primary-900">View</a>
                                                <a href="{{ route('transcripts.preview', $student['student']->id) }}" class="ml-3 text-primary-600 hover:text-primary-900">Transcript</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Chart Script -->
                @push('scripts')
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Calculate GPA ranges for the chart
                        const gpaData = {!! json_encode(array_map(function($item) { return $item['gpa']; }, $studentPerformance)) !!};
                        
                        const gpaRanges = {
                            '0.00-0.99': 0,
                            '1.00-1.99': 0,
                            '2.00-2.99': 0,
                            '3.00-3.49': 0,
                            '3.50-4.00': 0
                        };
                        
                        gpaData.forEach(gpa => {
                            if (gpa < 1.0) gpaRanges['0.00-0.99']++;
                            else if (gpa < 2.0) gpaRanges['1.00-1.99']++;
                            else if (gpa < 3.0) gpaRanges['2.00-2.99']++;
                            else if (gpa < 3.5) gpaRanges['3.00-3.49']++;
                            else gpaRanges['3.50-4.00']++;
                        });
                        
                        const ctx = document.getElementById('gpaDistributionChart').getContext('2d');
                        
                        const gpaChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: Object.keys(gpaRanges),
                                datasets: [{
                                    label: 'Number of Students',
                                    data: Object.values(gpaRanges),
                                    backgroundColor: [
                                        'rgba(255, 99, 132, 0.6)',
                                        'rgba(255, 159, 64, 0.6)',
                                        'rgba(255, 205, 86, 0.6)',
                                        'rgba(75, 192, 192, 0.6)',
                                        'rgba(54, 162, 235, 0.6)'
                                    ],
                                    borderColor: [
                                        'rgb(255, 99, 132)',
                                        'rgb(255, 159, 64)',
                                        'rgb(255, 205, 86)',
                                        'rgb(75, 192, 192)',
                                        'rgb(54, 162, 235)'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Number of Students'
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'GPA Range'
                                        }
                                    }
                                },
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'GPA Distribution'
                                    },
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    });
                </script>
                @endpush
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <p class="text-gray-600">Please select filters and generate the report.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
