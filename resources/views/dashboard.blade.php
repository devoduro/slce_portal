@extends('components.app-layout')

@section('title', 'Dashboard')
@section('subtitle', 'Overview of your transcript system')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Welcome Banner -->
    <div class="col-span-1 md:col-span-3">
        <div class="gradient-bg rounded-lg shadow-lg p-6 text-white">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Welcome to Transcript System</h2>
                    <p class="opacity-90">Manage student transcripts, courses, and academic records efficiently.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="{{ route('transcripts.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-primary-600 rounded-lg font-medium shadow-sm hover:bg-gray-50 transition-colors">
                        <i class="fas fa-file-alt mr-2"></i> View Transcripts
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="bg-gradient-to-br from-purple-400 to-purple-500 rounded-lg shadow-lg p-6 transform transition-all duration-200 hover:scale-105">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-white bg-opacity-20">
                <i class="fas fa-user-graduate text-2xl text-white"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-white text-opacity-90">Total Students</h3>
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-white">{{ $totalStudents ?? 0 }}</span>
                    @if(isset($studentGrowth))
                        <span class="ml-2 text-sm font-medium text-white bg-white bg-opacity-20 px-2 py-0.5 rounded-full">
                            <i class="fas fa-arrow-{{ $studentGrowth >= 0 ? 'up' : 'down' }}"></i> {{ abs($studentGrowth) }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('students.index') }}" class="text-sm text-white hover:text-opacity-75 font-medium inline-flex items-center">
                View all students <i class="fas fa-arrow-right ml-2 transition-transform group-hover:translate-x-1"></i>
            </a>
        </div>
    </div>

    <div class="bg-gradient-to-br from-orange-300 to-orange-400 rounded-lg shadow-lg p-6 transform transition-all duration-200 hover:scale-105">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-white bg-opacity-20">
                <i class="fas fa-book text-2xl text-white"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-white text-opacity-90">Total Courses</h3>
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-white">{{ $totalCourses ?? 0 }}</span>
                    @if(isset($courseGrowth))
                        <span class="ml-2 text-sm font-medium text-white bg-white bg-opacity-20 px-2 py-0.5 rounded-full">
                            <i class="fas fa-arrow-{{ $courseGrowth >= 0 ? 'up' : 'down' }}"></i> {{ abs($courseGrowth) }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('courses.index') }}" class="text-sm text-white hover:text-opacity-75 font-medium inline-flex items-center">
                View all courses <i class="fas fa-arrow-right ml-2 transition-transform group-hover:translate-x-1"></i>
            </a>
        </div>
    </div>

    <div class="bg-gradient-to-br from-cyan-400 to-cyan-500 rounded-lg shadow-lg p-6 transform transition-all duration-200 hover:scale-105">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-white bg-opacity-20">
                <i class="fas fa-chart-line text-2xl text-white"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-white text-opacity-90">Average GPA</h3>
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-white">{{ number_format($averageGPA, 2) }}</span>
                    @if(isset($transcriptGrowth))
                        <span class="ml-2 text-sm font-medium {{ $transcriptGrowth >= 0 ? 'text-green-500' : 'text-red-500' }}">
                            <i class="fas fa-arrow-{{ $transcriptGrowth >= 0 ? 'up' : 'down' }}"></i> {{ abs($transcriptGrowth) }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                <i class="fas fa-chart-line text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Average GPA</h3>
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-gray-800">{{ number_format($averageGPA, 2) }}</span>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('results.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                View all results <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>

    <!-- GPA Distribution Chart -->
    <div class="col-span-1 md:col-span-2 bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-800 mb-4">GPA Distribution</h3>
        <div class="h-64">
           
                <canvas id="gpaChart" class="w-full" style="height: 400px;"></canvas>
        
        </div>
    </div>

    <!-- Top Students -->
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Top Students</h3>
        <div class="space-y-4">
            @forelse($topStudents as $student)
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <img class="h-10 w-10 rounded-full" 
                             src="https://ui-avatars.com/api/?name={{ urlencode($student['name']) }}&background=0D9488&color=fff" 
                             alt="{{ $student['name'] }}">
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-gray-800">{{ $student['name'] }}</h4>
                        <p class="text-xs text-gray-500">CGPA: {{ number_format($student['cgpa'], 2) }}</p>
                        <p class="text-xs text-gray-400">{{ $student['programme']['name'] ?? 'N/A' }}</p>
                    </div>
                </div>
            @empty
                <div class="text-center py-4">
                    <p class="text-gray-500">No student data available.</p>
                </div>
            @endforelse
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="{{ route('students.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                View all students <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-span-1 md:col-span-2 bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-800">Recent Activities</h3>
            <div class="flex space-x-2">
                <button class="px-3 py-1 text-xs font-medium bg-primary-50 text-primary-600 rounded-full">All</button>
                <button class="px-3 py-1 text-xs font-medium text-gray-500 hover:bg-gray-100 rounded-full">Results</button>
                <button class="px-3 py-1 text-xs font-medium text-gray-500 hover:bg-gray-100 rounded-full">Transcripts</button>
            </div>
        </div>
        <div class="space-y-4 max-h-80 overflow-y-auto pr-2">
            @if(!empty($recentActivities))
                @foreach($recentActivities as $activity)
                    <div class="flex items-start">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-800">{{ $activity['message'] }}</p>
                            <div class="flex items-center mt-1">
                                <span class="text-xs text-gray-500">{{ $activity['user'] }}</span>
                                <span class="mx-1 text-gray-300">•</span>
                                <span class="text-xs text-gray-500">{{ $activity['time'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-4">
                    <p class="text-gray-500">No recent activities found.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Academic Calendar Overview -->
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Academic Calendar</h3>
        <div class="space-y-4">
            @if($currentAcademicYear)
                <div class="flex items-start">
                    <div class="min-w-[50px] text-center">
                        <div class="text-xs font-medium text-gray-500">Year</div>
                        <div class="text-lg font-bold gradient-text">{{ $academicYearProgress }}%</div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-gray-800">{{ $currentAcademicYear->name }}</h4>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ Carbon\Carbon::parse($currentAcademicYear->start_date)->format('M d, Y') }} - 
                            {{ Carbon\Carbon::parse($currentAcademicYear->end_date)->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            @endif

            @if($currentSemester)
                <div class="flex items-start">
                    <div class="min-w-[50px] text-center">
                        <div class="text-xs font-medium text-gray-500">Term</div>
                        <div class="text-lg font-bold gradient-text">{{ $semesterProgress }}%</div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-gray-800">{{ $currentSemester->name }}</h4>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ Carbon\Carbon::parse($currentSemester->start_date)->format('M d, Y') }} - 
                            {{ Carbon\Carbon::parse($currentSemester->end_date)->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="#" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                View all events <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>
@endsection

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
            labels: {!! json_encode(array_keys($gpaDistribution)) !!},
            data: {!! json_encode(array_values($gpaDistribution)) !!}
        });

        try {
            new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($gpaDistribution)) !!},
                datasets: [{
                    label: 'Number of Students',
                    data: {!! json_encode(array_values($gpaDistribution)) !!},
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
                        },
                        title: {
                            display: true,
                            text: 'Number of Students'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'CGPA Range'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'CGPA Distribution',
                        font: {
                            size: 16
                        }
                    }
                },
                maintainAspectRatio: false
            }
            });
        } catch (error) {
            console.error('Error creating chart:', error);
        }
    });
    </script>
    @endpush

 