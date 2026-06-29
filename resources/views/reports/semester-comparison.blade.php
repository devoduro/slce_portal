<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Semester Comparison Report') }}
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
                    <form action="{{ route('reports.semester-comparison') }}" method="GET" class="space-y-4">
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
                                <label for="programme_id" class="block text-sm font-medium text-gray-700">Programme (Optional)</label>
                                <select id="programme_id" name="programme_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">All Programmes</option>
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

            @if(isset($semesterData))
                <!-- Report Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Semester Comparison Report</h3>
                        <p class="text-gray-600">
                            Academic Year: <strong>{{ $academicYear->name }}</strong>
                            @if($programme)
                                | Programme: <strong>{{ $programme->name }}</strong>
                            @else
                                | All Programmes
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Performance Metrics Charts -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Average GPA by Semester</h3>
                                <div class="h-80">
                                    <canvas id="avgGpaChart"></canvas>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Pass Rate by Semester</h3>
                                <div class="h-80">
                                    <canvas id="passRateChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Combined Performance Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Semester Performance Comparison</h3>
                        <div class="w-full h-80">
                            <canvas id="combinedChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Semester Comparison Data</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Semester
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Average GPA
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Pass Rate
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Performance Indicator
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($semesterData as $data)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $data['semester']->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $data['average_gpa'] >= 3.5 ? 'text-green-600' : ($data['average_gpa'] >= 2.0 ? 'text-blue-600' : 'text-red-600') }}">
                                                {{ $data['average_gpa'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex items-center">
                                                    <span class="mr-2">{{ $data['pass_rate'] }}%</span>
                                                    <div class="w-24 bg-gray-200 rounded-full h-2.5">
                                                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $data['pass_rate'] }}%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @php
                                                    $performanceClass = 'text-gray-600';
                                                    $performanceIcon = 'fa-minus';
                                                    
                                                    if ($data['average_gpa'] >= 3.0 && $data['pass_rate'] >= 80) {
                                                        $performanceClass = 'text-green-600';
                                                        $performanceIcon = 'fa-arrow-up';
                                                    } elseif ($data['average_gpa'] >= 2.0 && $data['pass_rate'] >= 60) {
                                                        $performanceClass = 'text-blue-600';
                                                        $performanceIcon = 'fa-equals';
                                                    } else {
                                                        $performanceClass = 'text-red-600';
                                                        $performanceIcon = 'fa-arrow-down';
                                                    }
                                                @endphp
                                                <span class="{{ $performanceClass }}">
                                                    <i class="fas {{ $performanceIcon }} mr-1"></i>
                                                    {{ $data['average_gpa'] >= 3.0 && $data['pass_rate'] >= 80 ? 'Excellent' : 
                                                       ($data['average_gpa'] >= 2.0 && $data['pass_rate'] >= 60 ? 'Satisfactory' : 'Needs Improvement') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Analysis and Recommendations -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Analysis and Recommendations</h3>
                        
                        @php
                            $avgGpa = collect($semesterData)->avg('average_gpa');
                            $avgPassRate = collect($semesterData)->avg('pass_rate');
                            $bestSemester = collect($semesterData)->sortByDesc('average_gpa')->first();
                            $worstSemester = collect($semesterData)->sortBy('average_gpa')->first();
                        @endphp
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Performance Summary</h4>
                                <ul class="list-disc pl-5 space-y-2 text-sm text-gray-600">
                                    <li>Overall average GPA across all semesters: <strong>{{ number_format($avgGpa, 2) }}</strong></li>
                                    <li>Overall average pass rate: <strong>{{ number_format($avgPassRate, 2) }}%</strong></li>
                                    <li>Best performing semester: <strong>{{ $bestSemester['semester']->name }}</strong> with average GPA of <strong>{{ $bestSemester['average_gpa'] }}</strong></li>
                                    <li>Lowest performing semester: <strong>{{ $worstSemester['semester']->name }}</strong> with average GPA of <strong>{{ $worstSemester['average_gpa'] }}</strong></li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Recommendations</h4>
                                <ul class="list-disc pl-5 space-y-2 text-sm text-gray-600">
                                    @if($avgGpa < 2.0)
                                        <li>Consider reviewing curriculum and teaching methods to improve overall performance.</li>
                                        <li>Implement additional academic support programs for struggling students.</li>
                                    @elseif($avgGpa < 3.0)
                                        <li>Maintain current teaching standards while looking for areas of improvement.</li>
                                        <li>Provide targeted support for courses with lower pass rates.</li>
                                    @else
                                        <li>Continue with current successful teaching methods and support systems.</li>
                                        <li>Consider implementing advanced learning opportunities for high-performing students.</li>
                                    @endif
                                    <li>Investigate factors contributing to performance differences between semesters.</li>
                                    <li>Share best practices from the highest-performing semester with other teaching staff.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Script -->
                @push('scripts')
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Prepare data for charts
                        const semesterLabels = {!! json_encode(array_map(function($item) { return $item['semester']->name; }, $semesterData)) !!};
                        const avgGpas = {!! json_encode(array_map(function($item) { return $item['average_gpa']; }, $semesterData)) !!};
                        const passRates = {!! json_encode(array_map(function($item) { return $item['pass_rate']; }, $semesterData)) !!};
                        
                        // Average GPA Chart
                        const gpaCtx = document.getElementById('avgGpaChart').getContext('2d');
                        const gpaChart = new Chart(gpaCtx, {
                            type: 'bar',
                            data: {
                                labels: semesterLabels,
                                datasets: [{
                                    label: 'Average GPA',
                                    data: avgGpas,
                                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                    borderColor: 'rgb(54, 162, 235)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 4,
                                        title: {
                                            display: true,
                                            text: 'Average GPA'
                                        }
                                    }
                                },
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Average GPA by Semester'
                                    }
                                }
                            }
                        });
                        
                        // Pass Rate Chart
                        const passRateCtx = document.getElementById('passRateChart').getContext('2d');
                        const passRateChart = new Chart(passRateCtx, {
                            type: 'bar',
                            data: {
                                labels: semesterLabels,
                                datasets: [{
                                    label: 'Pass Rate (%)',
                                    data: passRates,
                                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                    borderColor: 'rgb(75, 192, 192)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 100,
                                        title: {
                                            display: true,
                                            text: 'Pass Rate (%)'
                                        }
                                    }
                                },
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Pass Rate by Semester'
                                    }
                                }
                            }
                        });
                        
                        // Combined Chart
                        const combinedCtx = document.getElementById('combinedChart').getContext('2d');
                        const combinedChart = new Chart(combinedCtx, {
                            type: 'line',
                            data: {
                                labels: semesterLabels,
                                datasets: [
                                    {
                                        label: 'Average GPA',
                                        data: avgGpas,
                                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                        borderColor: 'rgb(54, 162, 235)',
                                        borderWidth: 2,
                                        yAxisID: 'y',
                                        tension: 0.1
                                    },
                                    {
                                        label: 'Pass Rate (%)',
                                        data: passRates,
                                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                        borderColor: 'rgb(75, 192, 192)',
                                        borderWidth: 2,
                                        yAxisID: 'y1',
                                        tension: 0.1
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                        beginAtZero: true,
                                        max: 4,
                                        title: {
                                            display: true,
                                            text: 'Average GPA'
                                        }
                                    },
                                    y1: {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        beginAtZero: true,
                                        max: 100,
                                        title: {
                                            display: true,
                                            text: 'Pass Rate (%)'
                                        },
                                        grid: {
                                            drawOnChartArea: false
                                        }
                                    }
                                },
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Semester Performance Comparison'
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
