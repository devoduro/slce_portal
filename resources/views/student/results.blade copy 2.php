@extends('components.student-app-layout')

@section('header')
    <div class="flex flex-col space-y-1">
        <h2 class="text-2xl font-bold text-gray-800">Academic Results</h2>
        <p class="text-gray-600">View your comprehensive academic performance</p>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto py-6 space-y-8">
    <!-- Flash Messages -->
    <div class="fixed top-4 right-4 z-50 space-y-4" x-data="{ success: {{ session()->has('success') ? 'true' : 'false' }}, error: {{ session()->has('error') ? 'true' : 'false' }} }">
        @if(session('success'))
            <div 
                x-show="success" 
                x-transition:enter="transform ease-out duration-300 transition"
                x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-init="setTimeout(() => success = false, 5000)"
                class="max-w-sm w-full bg-green-50 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
                <div class="p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400 text-lg"></i>
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <p class="text-sm text-green-800">{{ session('success') }}</p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex">
                            <button @click="success = false" class="rounded-md inline-flex text-green-600 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div 
                x-show="error"
                x-transition:enter="transform ease-out duration-300 transition"
                x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-init="setTimeout(() => error = false, 5000)"
                class="max-w-sm w-full bg-red-50 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
                <div class="p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400 text-lg"></i>
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <p class="text-sm text-red-800">{{ session('error') }}</p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex">
                            <button @click="error = false" class="rounded-md inline-flex text-red-600 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Overall Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- CGPA Card -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-sm p-6 text-white relative overflow-hidden group hover:shadow-lg transition-shadow duration-300">
            <div class="relative z-10">
                <p class="text-blue-100 text-sm font-medium">Current CGPA</p>
                <div class="mt-2 flex items-baseline">
                    <h3 class="text-3xl font-bold">{{ number_format($cgpa, 2) }}</h3>
                    <span class="text-blue-200 ml-1">/4.00</span>
                </div>
                <p class="text-blue-100 text-sm mt-2">Overall Performance</p>
            </div>
            <div class="absolute right-0 bottom-0 transform translate-x-3 translate-y-3 transition-transform duration-300 group-hover:scale-110">
                <i class="fas fa-chart-line text-blue-400 opacity-50 text-6xl"></i>
            </div>
        </div>

        <!-- Classification Card -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-sm p-6 text-white relative overflow-hidden group hover:shadow-lg transition-shadow duration-300">
            <div class="relative z-10">
                <p class="text-emerald-100 text-sm font-medium">Classification</p>
                <div class="mt-2">
                    <h3 class="text-xl font-bold">{{ $classification ?? 'In Progress' }}</h3>
                </div>
                <p class="text-emerald-100 text-sm mt-2">Degree Status</p>
            </div>
            <div class="absolute right-0 bottom-0 transform translate-x-3 translate-y-3 transition-transform duration-300 group-hover:scale-110">
                <i class="fas fa-award text-emerald-400 opacity-50 text-6xl"></i>
            </div>
        </div>

        <!-- Credits Card -->
        <div class="bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl shadow-sm p-6 text-white relative overflow-hidden group hover:shadow-lg transition-shadow duration-300">
            <div class="relative z-10">
                <p class="text-violet-100 text-sm font-medium">Total Credits</p>
                <div class="mt-2">
                    <h3 class="text-3xl font-bold">{{ $totalCreditHours }}</h3>
                </div>
                <p class="text-violet-100 text-sm mt-2">Credit Hours Earned</p>
            </div>
            <div class="absolute right-0 bottom-0 transform translate-x-3 translate-y-3 transition-transform duration-300 group-hover:scale-110">
                <i class="fas fa-graduation-cap text-violet-400 opacity-50 text-6xl"></i>
            </div>
        </div>

        <!-- Academic Status Card -->
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-sm p-6 text-white relative overflow-hidden group hover:shadow-lg transition-shadow duration-300">
            <div class="relative z-10">
                <p class="text-amber-100 text-sm font-medium">Academic Status</p>
                <div class="mt-2">
                    <h3 class="text-xl font-bold">{{ $student->academic_status }}</h3>
                </div>
                <p class="text-amber-100 text-sm mt-2">Current Standing</p>
            </div>
            <div class="absolute right-0 bottom-0 transform translate-x-3 translate-y-3 transition-transform duration-300 group-hover:scale-110">
                <i class="fas fa-user-graduate text-amber-400 opacity-50 text-6xl"></i>
            </div>
        </div>
        <div class="bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-medium text-white mb-2">Current CGPA</h3>
                <div class="flex items-baseline">
                    <span class="text-3xl font-bold text-white">{{ number_format($cgpa, 2) }}</span>
                    <span class="text-primary-200 ml-2">/ 4.00</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-medium text-white mb-2">Classification</h3>
                <div class="text-3xl font-bold text-white">{{ $classification ?? 'N/A' }}</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-medium text-white mb-2">Total Credits</h3>
                <div class="text-3xl font-bold text-white">{{ $totalCreditHours }}</div>
            </div>
        </div>
    </div>

    <!-- Results by Academic Year -->
    @forelse($groupedResults as $academicYearId => $yearData)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" x-data="{ expanded: true }">
            <div class="bg-gray-800 px-6 py-4 cursor-pointer hover:bg-gray-700 transition-colors duration-200" @click="expanded = !expanded">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white">{{ $yearData['academic_year']->name }}</h3>
                    <button class="text-white hover:text-gray-200 focus:outline-none transition-transform duration-200" :class="{ 'transform rotate-180': !expanded }">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>

            <div class="divide-y divide-gray-200" x-show="expanded" x-collapse>
                @foreach($yearData['semesters'] as $semesterId => $semesterData)
                    <div class="p-6" x-data="{ semesterExpanded: true }">
                        <div class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-4 py-3 rounded-lg mb-6 cursor-pointer hover:from-primary-600 hover:to-primary-700 transition-colors duration-200" @click="semesterExpanded = !semesterExpanded">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium">{{ $semesterData['semester']->name }}</h4>
                                <button class="text-white hover:text-primary-100 focus:outline-none transition-transform duration-200" :class="{ 'transform rotate-180': !semesterExpanded }">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto" x-show="semesterExpanded" x-collapse>
                            <table class="min-w-full divide-y divide-gray-200 table-auto">
                                <colgroup>
                                    <col class="w-1/4">
                                    <col class="w-1/4">
                                    <col class="w-1/6">
                                    <col class="w-1/6">
                                    <col class="w-1/6">
                                </colgroup>
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-3 text-left">
                                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Course Details</span>
                                        </th>
                                        <th class="px-6 py-3 text-left">
                                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</span>
                                        </th>
                                        <th class="px-6 py-3 text-center">
                                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Credits</span>
                                        </th>
                                        <th class="px-6 py-3 text-center">
                                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</span>
                                        </th>
                                        <th class="px-6 py-3 text-center">
                                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Points</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($semesterData['results'] as $result)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-gray-900">{{ $result->course->code }}</span>
                                                    <span class="text-xs text-gray-500">Core Course</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900 line-clamp-2">{{ $result->course->title }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="text-sm font-medium text-gray-900">{{ $result->course->credit_hours }}</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex justify-center">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        @if(in_array($result->grade, ['A', 'A-'])) bg-green-100 text-green-800
                                                        @elseif(in_array($result->grade, ['B+', 'B', 'B-'])) bg-blue-100 text-blue-800
                                                        @elseif(in_array($result->grade, ['C+', 'C'])) bg-yellow-100 text-yellow-800
                                                        @else bg-red-100 text-red-800
                                                        @endif">
                                                        {{ $result->grade }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="text-sm font-medium text-gray-900">{{ number_format($result->grade_point, 2) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50 border-t-2 border-gray-200">
                                        <td colspan="3" class="px-6 py-4 text-sm font-medium text-gray-900 text-right">Semester GPA:</td>
                                        <td colspan="2" class="px-6 py-4">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-lg font-bold text-gray-900">{{ number_format($semesterData['gpa'], 2) }}</span>
                                                <span class="text-sm text-gray-500">/4.00</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach

                <!-- Year Summary -->
                <div class="bg-gray-50 px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center justify-between p-4 bg-white rounded-lg shadow-sm">
                            <span class="text-sm font-medium text-gray-600">Academic Year GPA</span>
                            <span class="text-lg font-bold text-gray-900">{{ number_format($yearGPAs[$academicYearId] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-white rounded-lg shadow-sm">
                            <span class="text-sm font-medium text-gray-600">Cumulative GPA</span>
                            <span class="text-lg font-bold text-gray-900">{{ number_format($cumulativeGPAs[$academicYearId] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-12">
            <div class="max-w-sm mx-auto">
                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="text-center">
                        <i class="fas fa-graduation-cap text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Results Found</h3>
                        <p class="text-gray-500 mb-6">Your academic results will appear here once they are published by your institution.</p>
                        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection