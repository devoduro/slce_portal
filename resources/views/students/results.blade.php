<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Student Results') }}
            </h2>
            <x-button href="{{ route('students.show', $student->id) }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Student') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Student Info -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">{{ $student->full_name }} - {{ $student->index_number }}</h3>
                        <p class="text-gray-600">Programme: {{ $student->programme->name }}</p>
                    </div>

                    <!-- Results -->
                    @foreach($groupedResults as $academicYear)
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-indigo-600 mb-4">{{ $academicYear['academic_year']->name }}</h4>
                            
                            @foreach($academicYear['semesters'] as $semester)
                                <div class="mb-6">
                                    <h5 class="font-semibold text-gray-700 mb-3">{{ $semester['semester']->name }}</h5>
                                    
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Code</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Title</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credit Hours</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GPA</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @php
                                                    $totalCredits = 0;
                                                    $totalGradePoints = 0;
                                                @endphp
                                                
                                                @foreach($semester['results'] as $result)
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $result->course->code }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $result->course->title }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $result->course->credit_hours }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($result->score, 1) }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $result->grade }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($result->gpa, 2) }}</td>
                                                    </tr>
                                                    @php
                                                        $totalCredits += $result->course->credit_hours;
                                                        $totalGradePoints += ($result->gpa * $result->course->credit_hours);
                                                    @endphp
                                                @endforeach
                                                
                                                <tr class="bg-indigo-50">
                                                    <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Semester GPA</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $totalCredits }}</td>
                                                    <td colspan="2"></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $totalCredits > 0 ? number_format($totalGradePoints / $totalCredits, 2) : '0.00' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
