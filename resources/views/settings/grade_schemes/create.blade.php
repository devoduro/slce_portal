@extends('components.app-layout')

@section('title', 'Create Grade Scheme')
@section('subtitle', 'Add a new grading system')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Create New Grade Scheme</h2>
        <p class="text-gray-600">Define a new grading system with letter grades, score ranges, and GPA values.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">Please fix the following errors:</p>
                    <ul class="mt-2 text-sm list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('settings.grade-schemes.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="col-span-1 md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Grade Scheme Name*</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. Standard 4.0 Scale" required>
                <p class="mt-1 text-xs text-gray-500">Enter a descriptive name for this grading scheme.</p>
            </div>

            <div class="col-span-1 md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. Standard grading scheme with letter grades A through F">{{ old('description') }}</textarea>
            </div>

            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center">
                    <input type="checkbox" name="is_default" id="is_default" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" {{ old('is_default') ? 'checked' : '' }}>
                    <label for="is_default" class="ml-2 block text-sm text-gray-700">Set as default grade scheme</label>
                </div>
                <p class="mt-1 text-xs text-gray-500">If checked, this will be set as the default grade scheme for new courses.</p>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Grade Definitions</h3>
            <p class="text-sm text-gray-500 mb-4">Define the grades for this scheme. You can add more grades by clicking the "Add Grade" button.</p>
            
            <div id="grades-container" class="space-y-4">
                <div class="grade-row grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Letter Grade*</label>
                        <input type="text" name="grades[0][letter]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. A" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Score*</label>
                        <input type="number" name="grades[0][min_score]" step="0.01" min="0" max="100" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. 90" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GPA Value*</label>
                        <input type="number" name="grades[0][gpa_value]" step="0.01" min="0" max="5" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. 4.0" required>
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="remove-grade px-3 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200" disabled>
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="button" id="add-grade" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    <i class="fas fa-plus mr-2"></i> Add Grade
                </button>
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4">
            <a href="{{ route('settings.grade-schemes') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Create Grade Scheme
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let gradeIndex = 1;
        const gradesContainer = document.getElementById('grades-container');
        const addGradeButton = document.getElementById('add-grade');
        
        addGradeButton.addEventListener('click', function() {
            const gradeRow = document.createElement('div');
            gradeRow.className = 'grade-row grid grid-cols-1 md:grid-cols-4 gap-4';
            gradeRow.innerHTML = `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Letter Grade*</label>
                    <input type="text" name="grades[${gradeIndex}][letter]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. B" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Score*</label>
                    <input type="number" name="grades[${gradeIndex}][min_score]" step="0.01" min="0" max="100" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. 80" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">GPA Value*</label>
                    <input type="number" name="grades[${gradeIndex}][gpa_value]" step="0.01" min="0" max="5" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. 3.0" required>
                </div>
                <div class="flex items-end">
                    <button type="button" class="remove-grade px-3 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            gradesContainer.appendChild(gradeRow);
            gradeIndex++;
            
            // Enable the first remove button if we have more than one grade
            if (gradesContainer.querySelectorAll('.grade-row').length > 1) {
                gradesContainer.querySelector('.remove-grade').removeAttribute('disabled');
            }
            
            // Add event listener to the new remove button
            gradeRow.querySelector('.remove-grade').addEventListener('click', function() {
                gradeRow.remove();
                
                // If only one grade remains, disable its remove button
                if (gradesContainer.querySelectorAll('.grade-row').length === 1) {
                    gradesContainer.querySelector('.remove-grade').setAttribute('disabled', 'disabled');
                }
            });
        });
    });
</script>
@endpush
@endsection
