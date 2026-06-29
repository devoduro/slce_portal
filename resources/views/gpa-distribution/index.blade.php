<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('CGPA Distribution') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <!-- Filters -->
                    <form method="GET" action="{{ route('gpa-distribution.index') }}" class="mb-8">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <label for="programme_id" class="block text-sm font-medium text-gray-700 mb-2">Programme</label>
                                <select name="programme_id" id="programme_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Programmes</option>
                                    @foreach($programmes as $programme)
                                        <option value="{{ $programme->id }}" {{ request('programme_id') == $programme->id ? 'selected' : '' }}>
                                            {{ $programme->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="min_gpa" class="block text-sm font-medium text-gray-700 mb-2">Min CGPA</label>
                                <input type="number" step="0.01" min="0" max="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" id="min_gpa" name="min_gpa" value="{{ request('min_gpa') }}">
                            </div>
                            <div>
                                <label for="max_gpa" class="block text-sm font-medium text-gray-700 mb-2">Max CGPA</label>
                                <input type="number" step="0.01" min="0" max="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" id="max_gpa" name="max_gpa" value="{{ request('max_gpa') }}">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-blue-600 rounded-lg shadow-md p-6 text-white">
                            <h5 class="text-sm font-medium opacity-75">Total Students</h5>
                            <p class="text-3xl font-bold mt-2">{{ $stats['total_students'] }}</p>
                        </div>
                        <div class="bg-green-600 rounded-lg shadow-md p-6 text-white">
                            <h5 class="text-sm font-medium opacity-75">Average CGPA</h5>
                            <p class="text-3xl font-bold mt-2">{{ number_format($stats['average_gpa'], 2) }}</p>
                        </div>
                        <div class="bg-cyan-600 rounded-lg shadow-md p-6 text-white">
                            <h5 class="text-sm font-medium opacity-75">Highest CGPA</h5>
                            <p class="text-3xl font-bold mt-2">{{ number_format($stats['highest_gpa'], 2) }}</p>
                        </div>
                        <div class="bg-yellow-500 rounded-lg shadow-md p-6 text-white">
                            <h5 class="text-sm font-medium opacity-75">Lowest CGPA</h5>
                            <p class="text-3xl font-bold mt-2">{{ number_format($stats['lowest_gpa'], 2) }}</p>
                        </div>
                    </div>

                    <!-- Chart and Distribution -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="md:col-span-2 bg-white rounded-lg shadow-md p-6">
                            <canvas id="gpaChart" class="w-full" style="height: 400px;"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h5 class="text-lg font-semibold text-gray-800 mb-4">Distribution Table</h5>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CGPA Range</th>
                                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($distribution as $range => $count)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $range }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $count }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $stats['total_students'] > 0 ? number_format(($count / $stats['total_students']) * 100, 1) : 0 }}%
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Students Table -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h5 class="text-lg font-semibold text-gray-800">Student List</h5>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CGPA</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester GPAs</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($students as $student)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ $student->index_number }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ $student->full_name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ $student->programme_name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($student->calculated_gpa, 2) }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <div class="space-y-1">
                                                    @foreach($student->semester_gpas as $gpa)
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-gray-600">{{ $gpa['period'] }}:</span>
                                                            <span class="font-medium">{{ number_format($gpa['gpa'], 2) }}</span>
                                                            <span class="text-gray-400">({{ $gpa['credits'] }} credits)</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('gpaChart');
        if (!canvas) {
            console.error('Canvas element not found');
            return;
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            console.error('Could not get 2d context');
            return;
        }

        console.log('Data:', {
            labels: {!! json_encode(array_keys($distribution)) !!},
            data: {!! json_encode(array_values($distribution)) !!}
        });

        try {
            new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($distribution)) !!},
                datasets: [{
                    label: 'Number of Students',
                    data: {!! json_encode(array_values($distribution)) !!},
                    backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(153, 102, 255, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'CGPA Distribution'
                    }
                }
            }
            });
        } catch (error) {
            console.error('Error creating chart:', error);
        }
    });
    </script>
    @endpush
</x-app-layout>