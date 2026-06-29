<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add Students to Course') }}: {{ $course->code }} - {{ $course->title }}
            </h2>
            <div class="flex space-x-2">
                <x-button href="{{ route('courses.students', $course->id) }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Students List') }}
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

                    @if($students->count() > 0)
                        <form action="{{ route('courses.students.store', $course->id) }}" method="POST">
                            @csrf
                            
                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Academic Year -->
                                    <div>
                                        <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Year <span class="text-red-500">*</span></label>
                                        <select id="academic_year_id" name="academic_year_id" required class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                            <option value="">Select Academic Year</option>
                                            @foreach($academicYears as $academicYear)
                                                <option value="{{ $academicYear->id }}" {{ old('academic_year_id') == $academicYear->id ? 'selected' : '' }}>
                                                    {{ $academicYear->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('academic_year_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <!-- Semester -->
                                    <div>
                                        <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1">Semester <span class="text-red-500">*</span></label>
                                        <select id="semester_id" name="semester_id" required class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                            <option value="">Select Semester</option>
                                            @foreach($semesters as $semester)
                                                <option value="{{ $semester->id }}" {{ old('semester_id') == $semester->id ? 'selected' : '' }}>
                                                    {{ $semester->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('semester_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Select Students to Add</h3>
                                
                                <div class="mb-4 flex items-center">
                                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <label for="select-all" class="ml-2 text-sm font-medium text-gray-700">Select All</label>
                                </div>
                                
                                @error('student_ids')
                                    <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($students as $student)
                                        <div class="bg-gray-50 p-3 rounded-lg flex items-start">
                                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" id="student-{{ $student->id }}" class="student-checkbox mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50" {{ in_array($student->id, old('student_ids', [])) ? 'checked' : '' }}>
                                            <label for="student-{{ $student->id }}" class="ml-2 flex-1">
                                                <div class="font-medium text-gray-900">{{ $student->full_name }}</div>
                                                <div class="text-sm text-gray-500">{{ $student->index_number }}</div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <x-button type="submit" class="ml-3" icon="fas fa-plus">
                                    {{ __('Add Selected Students') }}
                                </x-button>
                            </div>
                        </form>
                        
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const selectAllCheckbox = document.getElementById('select-all');
                                const studentCheckboxes = document.querySelectorAll('.student-checkbox');
                                
                                selectAllCheckbox.addEventListener('change', function() {
                                    const isChecked = this.checked;
                                    studentCheckboxes.forEach(checkbox => {
                                        checkbox.checked = isChecked;
                                    });
                                });
                                
                                // Update "Select All" checkbox state based on individual checkboxes
                                function updateSelectAllCheckbox() {
                                    const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
                                    selectAllCheckbox.checked = checkedCount === studentCheckboxes.length;
                                    selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < studentCheckboxes.length;
                                }
                                
                                studentCheckboxes.forEach(checkbox => {
                                    checkbox.addEventListener('change', updateSelectAllCheckbox);
                                });
                                
                                // Initial state
                                updateSelectAllCheckbox();
                            });
                        </script>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded">
                            <p class="font-medium">No eligible students found</p>
                            <p class="text-sm mt-1">All students from this programme are already enrolled in this course, or there are no students in this programme.</p>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <x-button href="{{ route('courses.show', $course->id) }}" variant="secondary" icon="fas fa-arrow-left">
                                {{ __('Back to Course Details') }}
                            </x-button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
