<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Course Details') }}
            </h2>
            <div class="flex space-x-2">
                <x-button href="{{ route('courses.edit', $course) }}" icon="fas fa-edit">
                    {{ __('Edit') }}
                </x-button>
                <x-button href="{{ route('courses.index') }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Courses') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Course Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row">
                        <!-- Course Basic Info -->
                        <div class="md:w-1/3 md:border-r md:pr-6">
                            <div class="mb-6">
                                <h3 class="text-2xl font-bold text-gray-900 mb-1">{{ $course->name }}</h3>
                                <p class="text-lg font-medium text-primary-600">{{ $course->code }}</p>
                                
                                <div class="flex items-center mt-3">
                                    @if($course->status === 'active')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @elseif($course->status === 'completed')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Completed
                                        </span>
                                    @elseif($course->status === 'inactive')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Inactive
                                        </span>
                                    @elseif($course->status === 'cancelled')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Cancelled
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Department</h4>
                                    <p class="mt-1">{{ $course->department }}</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Credits</h4>
                                    <p class="mt-1">{{ $course->credits }}</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Semester</h4>
                                    <p class="mt-1">{{ $course->semester->name ?? 'N/A' }}</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Academic Year</h4>
                                    <p class="mt-1">{{ $course->semester->academicYear->name ?? 'N/A' }}</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Programmes</h4>
                                    <div class="mt-1">
                                        @if($course->programmes->count() > 0)
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($course->programmes as $programme)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ $programme->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-gray-500">No programmes assigned</p>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($course->instructor)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Instructor</h4>
                                    <p class="mt-1">{{ $course->instructor }}</p>
                                </div>
                                @endif
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Students Enrolled</h4>
                                    <div class="mt-1 flex items-center">
                                        <span class="text-lg font-medium">{{ $course->students_count ?? 0 }}</span>
                                        @if($course->max_students)
                                            <span class="text-sm text-gray-500 ml-1">/ {{ $course->max_students }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <x-button href="{{ route('courses.students', $course) }}" class="w-full justify-center" icon="fas fa-users">
                                    {{ __('View Enrolled Students') }}
                                </x-button>
                            </div>
                        </div>
                        
                        <!-- Course Details -->
                        <div class="md:w-2/3 md:pl-6 mt-6 md:mt-0">
                            @if($course->description)
                            <div class="mb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-2">Description</h4>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">{{ $course->description }}</p>
                                </div>
                            </div>
                            @endif
                            
                            @if($course->prerequisites)
                            <div class="mb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-2">Prerequisites</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(explode(',', $course->prerequisites) as $prerequisite)
                                        <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">
                                            {{ trim($prerequisite) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            
                            <!-- Course Statistics -->
                            <div class="mb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Course Statistics</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-sm text-gray-500">Average Grade</p>
                                        <p class="font-medium text-xl">{{ isset($course->average_grade) ? number_format($course->average_grade, 1) : 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-sm text-gray-500">Pass Rate</p>
                                        <p class="font-medium text-xl">{{ isset($course->pass_rate) ? number_format($course->pass_rate * 100, 1) . '%' : 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-sm text-gray-500">Completion Rate</p>
                                        <p class="font-medium text-xl">{{ isset($course->completion_rate) ? number_format($course->completion_rate * 100, 1) . '%' : 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Grade Distribution -->
                            @if(isset($course->grade_distribution) && count($course->grade_distribution ?? []) > 0)
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Grade Distribution</h4>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="h-64">
                                        <canvas id="gradeDistributionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const ctx = document.getElementById('gradeDistributionChart').getContext('2d');
                                    
                                    @php
                                    $defaultData = [
                                        ['grade' => 'A', 'count' => 5],
                                        ['grade' => 'B', 'count' => 10],
                                        ['grade' => 'C', 'count' => 8],
                                        ['grade' => 'D', 'count' => 4],
                                        ['grade' => 'F', 'count' => 2]
                                    ];
                                    @endphp
                                    
                                    const gradeDistribution = @json($course->grade_distribution ?? $defaultData);
                                    
                                    const labels = gradeDistribution.map(item => item.grade);
                                    const data = gradeDistribution.map(item => item.count);
                                    
                                    const chart = new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: labels,
                                            datasets: [{
                                                label: 'Number of Students',
                                                data: data,
                                                backgroundColor: [
                                                    'rgba(14, 165, 233, 0.8)',
                                                    'rgba(7, 89, 133, 0.8)',
                                                    'rgba(59, 130, 246, 0.8)',
                                                    'rgba(99, 102, 241, 0.8)',
                                                    'rgba(139, 92, 246, 0.8)',
                                                ],
                                                borderColor: [
                                                    'rgba(14, 165, 233, 1)',
                                                    'rgba(7, 89, 133, 1)',
                                                    'rgba(59, 130, 246, 1)',
                                                    'rgba(99, 102, 241, 1)',
                                                    'rgba(139, 92, 246, 1)',
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
                                                    ticks: {
                                                        precision: 0
                                                    }
                                                }
                                            }
                                        }
                                    });
                                });
                            </script>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Enrolled Students -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Enrolled Students</h3>
                        <a href="{{ route('courses.students', $course) }}" class="text-sm text-primary-600 hover:text-primary-500">View All</a>
                    </div>
                    
                    @if(isset($course->students) && count($course->students) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Index Number
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Programme
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($course->students ?? [] as $student)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                @if($student->profile_photo)
                                                    <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('storage/' . $student->profile_photo) }}" alt="{{ $student->full_name }}">
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                                        <span class="text-primary-800 font-medium text-sm">{{ substr($student->full_name, 0, 2) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $student->full_name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $student->email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $student->index_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $student->programme->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($student->status === 'active')
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @elseif($student->status === 'graduated')
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                Graduated
                                            </span>
                                        @elseif($student->status === 'inactive')
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Inactive
                                            </span>
                                        @elseif($student->status === 'suspended')
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Suspended
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('students.show', $student) }}" class="text-primary-600 hover:text-primary-900">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-500">No students enrolled in this course yet.</p>
                        <x-button href="{{ route('courses.students.add', $course) }}" class="mt-2" size="sm" icon="fas fa-user-plus">
                            {{ __('Add Students') }}
                        </x-button>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 justify-end">
                <x-button href="{{ route('courses.students.add', $course) }}" variant="secondary" icon="fas fa-user-plus">
                    {{ __('Add Students') }}
                </x-button>
                <x-button href="{{ route('courses.results', $course) }}" variant="secondary" icon="fas fa-chart-line">
                    {{ __('Manage Results') }}
                </x-button>
                <form action="{{ route('courses.destroy', $course) }}" method="POST" class="inline-block">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="fas fa-trash" onclick="return confirm('Are you sure you want to delete this course? This action cannot be undone.')">
                        {{ __('Delete Course') }}
                    </x-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
