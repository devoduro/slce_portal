@extends('components.student-app-layout')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <!-- Student Info -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $student->full_name }} - {{ $student->index_number }}</h3>
                    <p class="text-gray-600">Programme: {{ $student->programme->name }}</p>
                </div>
                <!-- Header -->
                <div class="flex justify-between items-center">
                    <div class="flex flex-col space-y-1">
                        <h2 class="text-2xl font-bold text-gray-800">Academic Results</h2>
                        <p class="text-gray-600">View your comprehensive academic performance</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">{{ $student->programme->name }}</span>
                    </div>
                </div>
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
                <!-- Results -->
                @php
                    // Running totals used to derive the CGPA as at each semester below.
                    // Relies on $groupedResults being in chronological order (oldest year first,
                    // semesters ascending within a year) so each snapshot reflects everything up to that point.
                    $runningCreditHours = 0;
                    $runningCreditPoints = 0;
                @endphp
                @forelse($groupedResults as $academicYearId => $yearData)
                    <div class="mb-8">
                        <div class="bg-blue-50 p-6 rounded-lg mb-6 border border-blue-100">
                            @php
                                $yearTotalPoints = 0;
                                $yearTotalHours = 0;
                                foreach ($yearData['semesters'] as $semData) {
                                    foreach ($semData['results'] as $result) {
                                        $yearTotalPoints += $result->grade_point * $result->course->credit_hours;
                                        $yearTotalHours += $result->course->credit_hours;
                                    }
                                }
                                $yearGPA = $yearTotalHours > 0 ? $yearTotalPoints / $yearTotalHours : 0;
                            @endphp
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-xl font-bold text-blue-800">{{ $yearData['academic_year']->name }}</h4>
                                <div class="flex items-center space-x-6">
                                    <div class="text-right">
                                        <span class="block text-sm text-blue-600">Credit Hours</span>
                                        <span class="text-lg font-semibold text-blue-800">{{ $yearTotalHours }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-sm text-blue-600">Year GPA</span>
                                        <span class="text-lg font-semibold text-blue-800">{{ number_format($yearGPA, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="h-1 w-full bg-blue-200 rounded-full overflow-hidden">
                                <div class="h-1 bg-blue-600 rounded-full" style="width: {{ min($yearGPA / 4 * 100, 100) }}%"></div>
                            </div>
                        </div>
                        @foreach($yearData['semesters'] as $semesterId => $semesterData)
                            @php
                                $isCurrentSemester = $loop->last; // Assuming the last semester is the current one
                            @endphp
                            <div class="mb-6" x-data="{ open: {{ $isCurrentSemester ? 'true' : 'false' }} }">
                                <div @click="open = !open" 
                                     class="flex items-center justify-between p-4 bg-white rounded-t-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors duration-200">
                                    <h5 class="font-semibold text-gray-700">{{ $semesterData['semester']->name }}</h5>
                                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" 
                                         :class="{'transform rotate-180': open}"
                                         fill="none" 
                                         stroke="currentColor" 
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 transform translate-y-0"
                                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                                     class="border-l border-r border-b border-gray-200 rounded-b-lg">

                                <!-- Semester Results -->
                                @php
                                    $totalCreditHours = array_sum(array_map(function($result) {
                                        return $result->course->credit_hours;
                                    }, $semesterData['results']));

                                    $totalCreditPoints = array_sum(array_map(function($result) {
                                        return $result->course->credit_hours * $result->grade_point;
                                    }, $semesterData['results']));
                                    
                                    $semesterGPA = $totalCreditHours > 0 ? $totalCreditPoints / $totalCreditHours : 0;

                                    // CGPA as at this semester: running total through all prior semesters plus this one.
                                    $runningCreditHours += $totalCreditHours;
                                    $runningCreditPoints += $totalCreditPoints;
                                    $cgpaAsAtSemester = $runningCreditHours > 0 ? $runningCreditPoints / $runningCreditHours : 0;
                                @endphp
                                <div class="p-6 bg-white">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                        <div class="p-4 bg-blue-50 rounded-lg">
                                            <span class="text-sm text-blue-600 block mb-1">Semester GPA</span>
                                            <span class="text-2xl font-bold text-blue-700">{{ number_format($semesterGPA, 2) }}</span>
                                        </div>
                                        <div class="p-4 bg-indigo-50 rounded-lg">
                                            <span class="text-sm text-indigo-600 block mb-1">CGPA (as at this semester)</span>
                                            <span class="text-2xl font-bold text-indigo-700">{{ number_format($cgpaAsAtSemester, 2) }}</span>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <span class="text-sm text-gray-600 block mb-1">Credit Hours</span>
                                            <span class="text-2xl font-bold text-gray-700">{{ $totalCreditHours }}</span>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <span class="text-sm text-gray-600 block mb-1">Credit Points</span>
                                            <span class="text-2xl font-bold text-gray-700">{{ $totalCreditPoints }}</span>
                                        </div>
                                    </div>
                                    <!-- Course Results Table -->
                                    <div class="overflow-x-auto border rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead>
                                                <tr class="bg-gray-50">
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Course Code</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Title</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Grade</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Credit Hours</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Credit Points</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($semesterData['results'] as $result)
                                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">{{ $result->course->code }}</td>
                                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $result->course->title }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $result->grade == 'F' ? 'text-red-600' : 'text-green-600' }}">
                                                            {{ $result->grade }}
                                                            @if($result->is_repeated)
                                                                <span class="ml-1 px-1.5 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Resit</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $result->course->credit_hours }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($result->grade_point * $result->course->credit_hours, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Academic Year Summary -->
                        @php
                            $yearCreditHours = 0;
                            $yearCreditPoints = 0;
                            foreach ($yearData['semesters'] as $sem) {
                                foreach ($sem['results'] as $res) {
                                    $yearCreditHours += $res->course->credit_hours;
                                    $yearCreditPoints += $res->grade_point * $res->course->credit_hours;
                                }
                            }
                            $yearGPA = $yearCreditHours > 0 ? $yearCreditPoints / $yearCreditHours : 0;
                        @endphp
                        <div class="bg-gray-50 p-6 rounded-lg mt-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-white p-4 rounded-lg shadow-sm">
                                    <h6 class="text-sm font-medium text-gray-600 mb-2">Credit Hours</h6>
                                    <p class="text-2xl font-bold text-gray-900">{{ $yearCreditHours }}</p>
                                </div>
                                <div class="bg-white p-4 rounded-lg shadow-sm">
                                    <h6 class="text-sm font-medium text-gray-600 mb-2">Credit Points</h6>
                                    <p class="text-2xl font-bold text-gray-900">{{ $yearCreditPoints }}</p>
                                </div>
                                <div class="bg-white p-4 rounded-lg shadow-sm">
                                    <h6 class="text-sm font-medium text-gray-600 mb-2">Academic Year GPA</h6>
                                    <p class="text-2xl font-bold text-gray-900">{{ number_format($yearGPA, 2) }}</p>
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

                <!-- Overall Summary -->
                @if(count($groupedResults) > 0)
                    <div class="mt-8 bg-gradient-to-br from-blue-50 to-blue-100 p-8 rounded-lg border border-blue-200">
                        <h4 class="text-xl font-bold text-blue-900 mb-6">Overall Academic Performance</h4>
                        @php
                            $totalCreditHours = 0;
                            $totalCreditPoints = 0;
                            foreach ($groupedResults as $year) {
                                foreach ($year['semesters'] as $sem) {
                                    foreach ($sem['results'] as $res) {
                                        $totalCreditHours += $res->course->credit_hours;
                                        $totalCreditPoints += $res->grade_point * $res->course->credit_hours;
                                    }
                                }
                            }
                            $cumulativeGPA = $totalCreditHours > 0 ? $totalCreditPoints / $totalCreditHours : 0;
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-white p-6 rounded-lg shadow-sm border border-blue-100">
                                <div class="flex items-center justify-between mb-4">
                                    <h6 class="text-sm font-medium text-blue-600">Total Credit Hours</h6>
                                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </div>
                                <p class="text-3xl font-bold text-blue-900">{{ $totalCreditHours }}</p>
                            </div>
                            <div class="bg-white p-6 rounded-lg shadow-sm border border-blue-100">
                                <div class="flex items-center justify-between mb-4">
                                    <h6 class="text-sm font-medium text-blue-600">Total Credit Points</h6>
                                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <p class="text-3xl font-bold text-blue-900">{{ number_format($totalCreditPoints, 2) }}</p>
                            </div>
                            <div class="bg-white p-6 rounded-lg shadow-sm border border-blue-100">
                                <div class="flex items-center justify-between mb-4">
                                    <h6 class="text-sm font-medium text-blue-600">Cumulative GPA</h6>
                                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                                <p class="text-3xl font-bold text-blue-900">{{ number_format($cumulativeGPA, 2) }}</p>
                                <div class="mt-4">
                                    <div class="h-2 w-full bg-blue-100 rounded-full overflow-hidden">
                                        <div class="h-2 bg-blue-600 rounded-full" style="width: {{ min($cumulativeGPA / 4 * 100, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection