<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $semester->name }}
            </h2>
            <div class="flex space-x-2">
                <x-button href="{{ route('semesters.edit', $semester) }}" variant="secondary" icon="fas fa-edit">
                    {{ __('Edit') }}
                </x-button>
                <x-button href="{{ route('semesters.index') }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Semesters') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Semester Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Semester Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Name:</span>
                                    <p class="mt-1">{{ $semester->name }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Academic Year:</span>
                                    <p class="mt-1">{{ $semester->academicYear->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Semester Number:</span>
                                    <p class="mt-1">{{ $semester->semester_number }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Period:</span>
                                    <p class="mt-1">{{ $semester->start_date->format('M d, Y') }} - {{ $semester->end_date->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Status:</span>
                                    <p class="mt-1">
                                        @if($semester->is_current)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Current
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                {{ $semester->end_date->isPast() ? 'Past' : 'Upcoming' }}
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Statistics</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div class="text-sm font-medium text-blue-800">Courses</div>
                                    <div class="mt-1 text-2xl font-semibold text-blue-900">{{ $courses->count() }}</div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="text-sm font-medium text-green-800">Results</div>
                                    <div class="mt-1 text-2xl font-semibold text-green-900">{{ $resultStats->total_results ?? 0 }}</div>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <div class="text-sm font-medium text-yellow-800">Pass Rate</div>
                                    <div class="mt-1 text-2xl font-semibold text-yellow-900">
                                        @if(($resultStats->pass_count ?? 0) + ($resultStats->fail_count ?? 0) > 0)
                                            {{ round(($resultStats->pass_count / (($resultStats->pass_count ?? 0) + ($resultStats->fail_count ?? 0))) * 100) }}%
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <div class="text-sm font-medium text-purple-800">Avg. Score</div>
                                    <div class="mt-1 text-2xl font-semibold text-purple-900">
                                        {{ $resultStats->average_score ? number_format($resultStats->average_score, 1) : 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Courses in this Semester -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Courses ({{ $courses->count() }})</h3>
                    
                    @if($courses->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-gray-500">No courses assigned to this semester yet.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credits</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($courses as $course)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $course->code }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $course->title }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $course->programme->name ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $course->credit_hours }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $course->is_core ? 'Core' : 'Elective' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('courses.show', $course) }}" class="text-primary-600 hover:text-primary-900">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- GPA Distribution -->
            @if(isset($gpaDistribution) && count($gpaDistribution) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">GPA Distribution</h3>
                    
                    <div class="h-64">
                        <canvas id="gpaChart"></canvas>
                    </div>
                    
                    @push('scripts')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const ctx = document.getElementById('gpaChart').getContext('2d');
                            
                            const gpaData = {
                                labels: [
                                    @foreach($gpaDistribution as $range => $count)
                                        '{{ $range }}',
                                    @endforeach
                                ],
                                datasets: [{
                                    label: 'Number of Students',
                                    data: [
                                        @foreach($gpaDistribution as $range => $count)
                                            {{ $count }},
                                        @endforeach
                                    ],
                                    backgroundColor: [
                                        'rgba(54, 162, 235, 0.5)',
                                        'rgba(75, 192, 192, 0.5)',
                                        'rgba(255, 206, 86, 0.5)',
                                        'rgba(255, 159, 64, 0.5)',
                                        'rgba(255, 99, 132, 0.5)',
                                        'rgba(153, 102, 255, 0.5)'
                                    ],
                                    borderColor: [
                                        'rgba(54, 162, 235, 1)',
                                        'rgba(75, 192, 192, 1)',
                                        'rgba(255, 206, 86, 1)',
                                        'rgba(255, 159, 64, 1)',
                                        'rgba(255, 99, 132, 1)',
                                        'rgba(153, 102, 255, 1)'
                                    ],
                                    borderWidth: 1
                                }]
                            };
                            
                            new Chart(ctx, {
                                type: 'bar',
                                data: gpaData,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                precision: 0
                                            }
                                        }
                                    },
                                    plugins: {
                                        title: {
                                            display: true,
                                            text: 'Student GPA Distribution'
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
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
