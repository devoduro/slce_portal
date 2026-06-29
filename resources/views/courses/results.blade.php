<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Course Results') }}: {{ $course->code }} - {{ $course->title }}
            </h2>
            <div class="flex space-x-2">
                <x-button href="{{ route('results.bulk-create') }}" variant="primary" icon="fas fa-upload">
                    {{ __('Bulk Upload Results') }}
                </x-button>
                <x-button href="{{ route('courses.show', $course->id) }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Course Details') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Course Info Summary -->
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Course Code</p>
                                <p class="text-lg font-semibold">{{ $course->code }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Programme</p>
                                <p class="text-lg font-semibold">{{ $course->programme->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Semester</p>
                                <p class="text-lg font-semibold">{{ $course->semester->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Statistics Cards -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Results Statistics</h3>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <p class="text-sm font-medium text-blue-700">Total Students</p>
                                <p class="text-2xl font-bold text-blue-800">{{ $stats['total'] }}</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <p class="text-sm font-medium text-green-700">Average Score</p>
                                <p class="text-2xl font-bold text-green-800">{{ number_format($stats['average'], 1) }}</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <p class="text-sm font-medium text-purple-700">Highest Score</p>
                                <p class="text-2xl font-bold text-purple-800">{{ number_format($stats['highest'], 1) }}</p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <p class="text-sm font-medium text-yellow-700">Lowest Score</p>
                                <p class="text-2xl font-bold text-yellow-800">{{ number_format($stats['lowest'], 1) }}</p>
                            </div>
                            <div class="bg-indigo-50 p-4 rounded-lg">
                                <p class="text-sm font-medium text-indigo-700">Pass Rate</p>
                                <p class="text-2xl font-bold text-indigo-800">{{ number_format($stats['pass_rate'], 1) }}%</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Form -->
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Results</h3>
                        <form action="{{ route('courses.results', $course->id) }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                                <select id="academic_year_id" name="academic_year_id" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="">All Academic Years</option>
                                    @foreach($academicYears as $academicYear)
                                        <option value="{{ $academicYear->id }}" {{ request('academic_year_id') == $academicYear->id ? 'selected' : '' }}>
                                            {{ $academicYear->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                                <select id="semester_id" name="semester_id" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="">All Semesters</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}" {{ request('semester_id') == $semester->id ? 'selected' : '' }}>
                                            {{ $semester->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="flex items-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    <i class="fas fa-filter mr-2"></i> {{ __('Apply Filters') }}
                                </button>
                                
                                @if(request('academic_year_id') || request('semester_id'))
                                    <a href="{{ route('courses.results', $course->id) }}" class="ml-2 inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-400 focus:ring ring-gray-200 disabled:opacity-25 transition ease-in-out duration-150">
                                        <i class="fas fa-times mr-2"></i> {{ __('Clear') }}
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <!-- Results Table -->
                    @if($results->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class Score</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam Score</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($results as $result)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $result->student->full_name ?? 'N/A' }}</div>
                                                <div class="text-sm text-gray-500">{{ $result->student->index_number ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $result->academicYear->name ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $result->semester->name ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $result->class_score }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $result->exam_score }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $result->total_score }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $gradeClass = 'bg-gray-100 text-gray-800';
                                                    if ($result->grade == 'A') {
                                                        $gradeClass = 'bg-green-100 text-green-800';
                                                    } elseif (in_array($result->grade, ['B+', 'B'])) {
                                                        $gradeClass = 'bg-blue-100 text-blue-800';
                                                    } elseif (in_array($result->grade, ['C+', 'C'])) {
                                                        $gradeClass = 'bg-yellow-100 text-yellow-800';
                                                    } elseif (in_array($result->grade, ['D+', 'D'])) {
                                                        $gradeClass = 'bg-orange-100 text-orange-800';
                                                    } elseif ($result->grade == 'F') {
                                                        $gradeClass = 'bg-red-100 text-red-800';
                                                    }
                                                @endphp
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $gradeClass }}">
                                                    {{ $result->grade }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('results.edit', $result->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            {{ $results->links() }}
                        </div>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded">
                            <p class="font-medium">No results found</p>
                            <p class="text-sm mt-1">There are no results recorded for this course yet.</p>
                        </div>
                    @endif
                    
                    <div class="mt-6 flex justify-between">
                        <div>
                            <x-button href="{{ route('courses.show', $course->id) }}" variant="secondary" icon="fas fa-arrow-left">
                                {{ __('Back to Course Details') }}
                            </x-button>
                        </div>
                        <div>
                            <x-button href="{{ route('courses.export', $course->id) }}" variant="success" icon="fas fa-file-excel">
                                {{ __('Export Results') }}
                            </x-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
