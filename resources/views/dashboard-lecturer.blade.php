@extends('components.app-layout')

@section('title', 'Dashboard')
@section('subtitle', 'Your courses, classes and students')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <!-- Welcome Banner -->
    <div class="col-span-1 md:col-span-4">
        <div class="gradient-bg rounded-lg shadow-lg p-6 text-white">
            <h2 class="text-2xl font-bold mb-2">Welcome back!</h2>
            <p class="opacity-90">Here's an overview of the courses and classes assigned to you.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="bg-green-500 rounded-2xl shadow-lg p-6 flex items-center justify-between">
        <div>
            <div class="text-4xl font-bold text-white">{{ $courses->count() }}</div>
            <div class="text-white text-opacity-90 font-medium mt-1">My Courses</div>
        </div>
        <div class="w-14 h-14 rounded-full bg-white bg-opacity-20 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-book text-2xl text-white"></i>
        </div>
    </div>

    <div class="bg-orange-500 rounded-2xl shadow-lg p-6 flex items-center justify-between">
        <div>
            <div class="text-4xl font-bold text-white">{{ $classGroups->count() }}</div>
            <div class="text-white text-opacity-90 font-medium mt-1">My Classes</div>
        </div>
        <div class="w-14 h-14 rounded-full bg-white bg-opacity-20 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-users text-2xl text-white"></i>
        </div>
    </div>

    <div class="bg-red-500 rounded-2xl shadow-lg p-6 flex items-center justify-between">
        <div>
            <div class="text-4xl font-bold text-white">{{ $studentCount }}</div>
            <div class="text-white text-opacity-90 font-medium mt-1">My Students</div>
        </div>
        <div class="w-14 h-14 rounded-full bg-white bg-opacity-20 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-user-graduate text-2xl text-white"></i>
        </div>
    </div>

    <div class="bg-blue-500 rounded-2xl shadow-lg p-6 flex items-center justify-between">
        <div>
            <div class="text-4xl font-bold text-white">{{ rtrim(rtrim(number_format($workload['workload'], 2), '0'), '.') }}</div>
            <div class="text-white text-opacity-90 font-medium mt-1">My Workload</div>
            <div class="text-xs text-white text-opacity-80 mt-0.5">{{ $workload['classes'] }} class{{ $workload['classes'] === 1 ? '' : 'es' }}{{ $currentSemester ? ' this semester' : '' }}</div>
        </div>
        <div class="w-14 h-14 rounded-full bg-white bg-opacity-20 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-weight-hanging text-2xl text-white"></i>
        </div>
    </div>

    <!-- Today's Classes -->
    <div class="col-span-1 md:col-span-4 bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Today's Classes</h3>
                <a href="{{ route('lecturer.timetable') }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                    View Full Timetable
                </a>
            </div>
        </div>
        @if($todayEntries->isEmpty())
            <div class="p-8 text-center text-gray-400">
                No classes scheduled for today ({{ now()->format('l') }}).
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($todayEntries as $entry)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-800">{{ $entry->course->code ?? 'N/A' }} &middot; {{ $entry->classGroup->name ?? 'N/A' }}</h4>
                            <p class="text-sm text-gray-500">{{ $entry->course->title ?? '' }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-800">{{ substr($entry->start_time, 0, 5) }} - {{ substr($entry->end_time, 0, 5) }}</div>
                            <div class="text-sm text-gray-500 mt-1">
                                @if($entry->is_virtual)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-laptop"></i> Online</span>
                                @else
                                    {{ $entry->venue->name ?? 'N/A' }}
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- My Courses -->
    <div class="col-span-1 md:col-span-4 bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800">My Courses</h3>
        </div>
        @if($courses->isEmpty())
            <div class="p-8 text-center text-gray-400">
                No courses assigned to you yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($courses as $course)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $course->code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $course->title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $course->semester->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <a href="{{ route('continuous-assessment.show', $course) }}" class="text-primary-600 hover:text-primary-900">CA Scores</a>
                                    <a href="{{ route('results.filter.course', ['course_id' => $course->id]) }}" class="text-indigo-600 hover:text-indigo-900">Results</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
