<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Lecturers') }}
            </h2>
            <div class="flex gap-2">
                <x-button href="{{ route('lecturers.export.excel', request()->query()) }}" variant="secondary" icon="fas fa-file-excel">
                    {{ __('Export Excel') }}
                </x-button>
                <x-button href="{{ route('lecturers.export.pdf', request()->query()) }}" variant="secondary" icon="fas fa-file-pdf" target="_blank">
                    {{ __('Export PDF') }}
                </x-button>
                <x-button href="{{ route('lecturers.import.form') }}" variant="secondary" icon="fas fa-file-import">
                    {{ __('Import Lecturers') }}
                </x-button>
                <x-button href="{{ route('lecturers.create') }}" icon="fas fa-plus">
                    {{ __('Add Lecturer') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('lecturers.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <div class="md:col-span-2">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email or staff ID" class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <select name="department_id" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ (string) request('department_id') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="course_id" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Courses Taught</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ (string) request('course_id') === (string) $course->id ? 'selected' : '' }}>{{ $course->code }} - {{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                            @if(request()->hasAny(['search', 'department_id', 'course_id']))
                                <a href="{{ route('lecturers.index') }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-primary-600">
                                    Clear
                                </a>
                            @endif
                        </div>
                    </form>

                    @if($lecturers->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-chalkboard-teacher text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-500">No lecturers found</h3>
                            <p class="text-gray-400 mt-1">Add a lecturer to assign them to courses and timetable slots</p>
                            <div class="mt-6">
                                <x-button href="{{ route('lecturers.create') }}" icon="fas fa-plus">
                                    {{ __('Add Lecturer') }}
                                </x-button>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Courses</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Workload (Current Semester)</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($lecturers as $lecturer)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    @if($lecturer->profile_photo)
                                                        <img src="{{ asset('storage/' . $lecturer->profile_photo) }}" alt="{{ $lecturer->name }}" class="w-9 h-9 rounded-full object-cover">
                                                    @else
                                                        <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 text-xs">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">{{ $lecturer->name }}</div>
                                                        <div class="text-sm text-gray-500">{{ $lecturer->staff_id ?? '-' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div>{{ $lecturer->email ?? '-' }}</div>
                                                <div>{{ $lecturer->phone ?? '' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $lecturer->department->name ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $lecturer->courses_count }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $lecturer->workload_classes }} class{{ $lecturer->workload_classes === 1 ? '' : 'es' }},
                                                {{ rtrim(rtrim(number_format($lecturer->workload_credit, 2), '0'), '.') }} credit hrs
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="{{ route('lecturers.show', $lecturer) }}" class="text-primary-600 hover:text-primary-900" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('lecturers.edit', $lecturer) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('lecturers.destroy', $lecturer) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this lecturer?')">
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

                        <div class="mt-4">
                            {{ $lecturers->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
