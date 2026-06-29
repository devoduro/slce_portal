@extends('components.app-layout')

@section('title', 'Create Academic Year')
@section('subtitle', 'Add a new academic year to the system')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Create Academic Year</h2>
            <p class="text-gray-600">Add a new academic year to the system</p>
        </div>
        <a href="{{ route('settings.academic-years') }}" 
           class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to List
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium">There were errors with your submission</h3>
                    <div class="mt-2 text-sm">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('settings.academic-years.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name Input -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">
                    Academic Year Name*
                </label>
                <input type="text" name="name" id="name" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 @error('name') border-red-300 text-red-900 placeholder-red-300 focus:outline-none focus:ring-red-500 focus:border-red-500 @enderror" 
                    placeholder="e.g. 2023/2024" value="{{ old('name') }}" required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Start Date Input -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">
                    Start Date*
                </label>
                <input type="date" name="start_date" id="start_date" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 @error('start_date') border-red-300 text-red-900 placeholder-red-300 focus:outline-none focus:ring-red-500 focus:border-red-500 @enderror" 
                    value="{{ old('start_date') }}" required>
                @error('start_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- End Date Input -->
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">
                    End Date*
                </label>
                <input type="date" name="end_date" id="end_date" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 @error('end_date') border-red-300 text-red-900 placeholder-red-300 focus:outline-none focus:ring-red-500 focus:border-red-500 @enderror" 
                    value="{{ old('end_date') }}" required>
                @error('end_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Is Current Checkbox -->
            <div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_current" id="is_current" value="1"
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" 
                        {{ old('is_current') ? 'checked' : '' }}>
                    <label for="is_current" class="ml-2 block text-sm text-gray-900">
                        Set as Current Academic Year
                    </label>
                </div>
                @error('is_current')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Semesters Section -->
        <div class="mt-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Semesters</h3>
                <button type="button" id="add-semester" 
                    class="px-3 py-1.5 text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <i class="fas fa-plus mr-2"></i>
                    Add Semester
                </button>
            </div>

            <div id="semesters-container">
                <!-- Initial Semester -->
                <div class="semester-item bg-gray-50 p-4 rounded-md mb-4 relative">
                    <button type="button" class="remove-semester absolute top-2 right-2 text-gray-500 hover:text-red-500">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semester Number*</label>
                            <input type="number" name="semesters[0][semester_number]" value="1" min="1" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semester Name*</label>
                            <input type="text" name="semesters[0][name]" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" 
                                placeholder="e.g. First Semester" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date*</label>
                            <input type="date" name="semesters[0][start_date]" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date*</label>
                            <input type="date" name="semesters[0][end_date]" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="flex justify-end space-x-3 pt-4">
            <a href="{{ route('settings.academic-years') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Create Academic Year
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Existing academic years for validation
        const existingYears = @json($existingYears ?? []);
        const nameInput = document.getElementById('name');
        const form = document.querySelector('form');
        const semestersContainer = document.getElementById('semesters-container');
        const addSemesterButton = document.getElementById('add-semester');
        let semesterCount = 1; // Start with 1 since we already have semester[0]
        
        // Add validation for duplicate names
        if (nameInput && form) {
            form.addEventListener('submit', function(e) {
                const value = nameInput.value.trim();
                if (existingYears.includes(value)) {
                    e.preventDefault();
                    alert('This academic year name already exists. Please choose a different name.');
                }
            });
        }
        
        // Start date and end date validation for academic year
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        
        if (startDate && endDate) {
            endDate.addEventListener('change', function() {
                if (startDate.value && this.value && this.value <= startDate.value) {
                    alert('End date must be after start date');
                    endDate.value = '';
                }
            });
            
            startDate.addEventListener('change', function() {
                if (endDate.value && this.value && endDate.value <= this.value) {
                    alert('End date must be after start date');
                    endDate.value = '';
                }
            });
        }

        // Function to validate semester dates
        function validateSemesterDates(semesterItem) {
            const startInput = semesterItem.querySelector('input[name$="[start_date]"]');
            const endInput = semesterItem.querySelector('input[name$="[end_date]"]');

            if (startInput && endInput) {
                endInput.addEventListener('change', function() {
                    if (startInput.value && this.value && this.value <= startInput.value) {
                        alert('Semester end date must be after start date');
                        this.value = '';
                    }
                });

                startInput.addEventListener('change', function() {
                    if (endInput.value && this.value && endInput.value <= this.value) {
                        alert('Semester end date must be after start date');
                        endInput.value = '';
                    }
                });
            }
        }

        // Add validation to initial semester
        const initialSemester = document.querySelector('.semester-item');
        if (initialSemester) {
            validateSemesterDates(initialSemester);
            
            // Add event listener to initial semester's remove button
            const initialRemoveButton = initialSemester.querySelector('.remove-semester');
            if (initialRemoveButton) {
                initialRemoveButton.addEventListener('click', function() {
                    initialSemester.remove();
                });
            }
        }

        // Add new semester
        if (addSemesterButton) {
            addSemesterButton.addEventListener('click', function() {
                const semesterItem = document.createElement('div');
                semesterItem.className = 'semester-item bg-gray-50 p-4 rounded-md mb-4 relative';

                semesterItem.innerHTML = `
                    <button type="button" class="remove-semester absolute top-2 right-2 text-gray-500 hover:text-red-500">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semester Number*</label>
                            <input type="number" name="semesters[${semesterCount}][semester_number]" value="${semesterCount + 1}" min="1" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semester Name*</label>
                            <input type="text" name="semesters[${semesterCount}][name]" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" 
                                placeholder="e.g. Second Semester" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date*</label>
                            <input type="date" name="semesters[${semesterCount}][start_date]" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date*</label>
                            <input type="date" name="semesters[${semesterCount}][end_date]" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                        </div>
                    </div>
                `;
                
                semestersContainer.appendChild(semesterItem);
                validateSemesterDates(semesterItem);
                semesterCount++;
                
                // Add event listener to the remove button
                const removeButton = semesterItem.querySelector('.remove-semester');
                if (removeButton) {
                    removeButton.addEventListener('click', function() {
                        semesterItem.remove();
                    });
                }
            });
        }

        // Form validation
        if (form) {
            form.addEventListener('submit', function(e) {
                const semesters = document.querySelectorAll('.semester-item');
                if (semesters.length === 0) {
                    e.preventDefault();
                    alert('Please add at least one semester to the academic year.');
                }
            });
        }
    });
</script>
@endpush

@endsection
