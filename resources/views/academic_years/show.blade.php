@extends('components.app-layout')

@section('title', 'Academic Year Details')
@section('subtitle', 'View and manage academic year details')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">{{ $academicYear->name }}</h2>
            <p class="text-gray-600">Academic year details and semesters.</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('academic-years.edit', $academicYear->id) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            <a href="{{ route('academic-years.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-gray-50 p-4 rounded-md">
            <h3 class="text-lg font-medium text-gray-900 mb-3">Academic Year Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Name:</p>
                    <p class="text-sm text-gray-900">{{ $academicYear->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Status:</p>
                    <p class="text-sm">
                        @if($academicYear->is_current)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Current
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Inactive
                            </span>
                            <form action="{{ route('academic-years.set-current', $academicYear->id) }}" method="POST" class="inline-block ml-2">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="text-xs text-green-600 hover:text-green-900">
                                    Set as Current
                                </button>
                            </form>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Start Date:</p>
                    <p class="text-sm text-gray-900">{{ $academicYear->start_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">End Date:</p>
                    <p class="text-sm text-gray-900">{{ $academicYear->end_date->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Semesters</h3>
            <button type="button" id="add-semester-btn" class="px-3 py-1.5 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="fas fa-plus mr-1"></i> Add Semester
            </button>
        </div>

        <!-- Add Semester Form (Hidden by default) -->
        <div id="add-semester-form" class="mb-6 hidden">
            <form action="{{ route('academic-years.add-semester', $academicYear->id) }}" method="POST" class="bg-gray-50 p-4 rounded-md">
                @csrf
                <h4 class="text-md font-medium text-gray-900 mb-3">Add New Semester</h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Semester Number*</label>
                        <input type="number" name="semester_number" value="{{ $academicYear->semesters->count() + 1 }}" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Semester Name*</label>
                        <input type="text" name="name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. Second Semester" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date*</label>
                        <input type="date" name="start_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date*</label>
                        <input type="date" name="end_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                    </div>
                </div>
                <div class="mt-4 flex justify-end space-x-2">
                    <button type="button" id="cancel-add-semester" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" class="px-3 py-1.5 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Add Semester
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($academicYear->semesters as $semester)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $semester->semester_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $semester->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    {{ $semester->start_date->format('M d, Y') }} - {{ $semester->end_date->format('M d, Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form action="{{ route('academic-years.remove-semester', ['academicYear' => $academicYear->id, 'semester' => $semester->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this semester?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Remove">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                No semesters found for this academic year.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addSemesterBtn = document.getElementById('add-semester-btn');
        const addSemesterForm = document.getElementById('add-semester-form');
        const cancelAddSemester = document.getElementById('cancel-add-semester');
        
        addSemesterBtn.addEventListener('click', function() {
            addSemesterForm.classList.remove('hidden');
            addSemesterBtn.classList.add('hidden');
        });
        
        cancelAddSemester.addEventListener('click', function() {
            addSemesterForm.classList.add('hidden');
            addSemesterBtn.classList.remove('hidden');
        });
    });
</script>
@endsection
@endsection
