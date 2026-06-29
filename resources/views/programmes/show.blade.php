@extends('components.app-layout')

@section('title', $programme->name)
@section('subtitle', 'Programme Details')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">{{ $programme->name }} ({{ $programme->code }})</h2>
            <p class="text-gray-600">{{ $programme->description }}</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('programmes.edit', $programme->id) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            <a href="{{ route('programmes.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gray-50 p-4 rounded-md">
            <h3 class="text-lg font-medium text-gray-900 mb-3">Programme Information</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-sm font-medium text-gray-500">Code:</p>
                    <p class="text-sm text-gray-900">{{ $programme->code }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Name:</p>
                    <p class="text-sm text-gray-900">{{ $programme->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Duration:</p>
                    <p class="text-sm text-gray-900">{{ $programme->duration_years }} years</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Department:</p>
                    <p class="text-sm text-gray-900">{{ $programme->department }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Faculty:</p>
                    <p class="text-sm text-gray-900">{{ $programme->faculty }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-md">
            <h3 class="text-lg font-medium text-gray-900 mb-3">Student Statistics</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium text-gray-500">Total Students:</p>
                    <p class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">{{ $totalStudents }}</p>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium text-gray-500">Active Students:</p>
                    <p class="text-sm bg-green-100 text-green-800 px-2 py-1 rounded-full">{{ $activeStudents }}</p>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium text-gray-500">Graduated Students:</p>
                    <p class="text-sm bg-purple-100 text-purple-800 px-2 py-1 rounded-full">{{ $graduatedStudents }}</p>
                </div>
                <div class="mt-4">
                    <a href="{{ route('students.index', ['programme_id' => $programme->id]) }}" class="text-primary-600 hover:text-primary-900 text-sm">
                        <i class="fas fa-users mr-1"></i> View All Students
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-md">
            <h3 class="text-lg font-medium text-gray-900 mb-3">Course Statistics</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium text-gray-500">Total Courses:</p>
                    <p class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">{{ $programme->courses->count() }}</p>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium text-gray-500">Total Credit Hours:</p>
                    <p class="text-sm bg-amber-100 text-amber-800 px-2 py-1 rounded-full">{{ $programme->courses->sum('credit_hours') }}</p>
                </div>
                <div class="mt-4">
                    <a href="{{ route('courses.create', ['programme_id' => $programme->id]) }}" class="text-primary-600 hover:text-primary-900 text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Course
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Programme Courses</h3>
            <a href="{{ route('courses.create', ['programme_id' => $programme->id]) }}" class="px-3 py-1.5 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="fas fa-plus mr-1"></i> Add Course
            </a>
        </div>

        @if($coursesBySemester->count() > 0)
            <div class="space-y-6">
                @foreach($coursesBySemester as $semesterId => $courses)
                    @php
                        $semester = App\Models\Semester::find($semesterId);
                        $semesterName = $semester ? $semester->name : 'Unknown Semester';
                        $academicYear = $semester && $semester->academicYear ? $semester->academicYear->name : 'Unknown Academic Year';
                    @endphp
                    <div class="bg-gray-50 p-4 rounded-md">
                        <h4 class="text-md font-medium text-gray-900 mb-3">{{ $semesterName }} ({{ $academicYear }})</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credit Hours</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($courses as $course)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $course->code }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $course->title }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $course->credit_hours }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $course->is_core ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                                    {{ $course->is_core ? 'Core' : 'Elective' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="{{ route('courses.show', $course->id) }}" class="text-primary-600 hover:text-primary-900" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('courses.edit', $course->id) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('courses.destroy', $course->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-50 p-6 text-center rounded-md">
                <p class="text-gray-500">No courses have been added to this programme yet.</p>
                <a href="{{ route('courses.create', ['programme_id' => $programme->id]) }}" class="mt-2 inline-block text-primary-600 hover:text-primary-900">
                    <i class="fas fa-plus mr-1"></i> Add the first course
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
