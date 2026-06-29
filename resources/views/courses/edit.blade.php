<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Course') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('courses.update', $course->id) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Course Code -->
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Course Code <span class="text-red-500">*</span></label>
                                <input type="text" name="code" id="code" value="{{ old('code', $course->code) }}" required class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                @error('code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Course Title -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Course Title <span class="text-red-500">*</span></label>
                                <input type="text" name="title" id="title" value="{{ old('title', $course->title) }}" required class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                @error('title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Credit Hours -->
                            <div>
                                <label for="credit_hours" class="block text-sm font-medium text-gray-700 mb-1">Credit Hours <span class="text-red-500">*</span></label>
                                <input type="number" step="0.5" min="0" name="credit_hours" id="credit_hours" value="{{ old('credit_hours', $course->credit_hours) }}" required class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                @error('credit_hours')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Programmes -->
                            <div>
                                <label for="programmes" class="block text-sm font-medium text-gray-700 mb-1">Programmes <span class="text-red-500">*</span></label>
                                <p class="text-xs text-gray-500 mb-1">Select all programmes that can take this course</p>
                                <select id="programmes" name="programme_ids[]" multiple required class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md" size="5">
                                    @foreach($programmes as $programme)
                                        <option value="{{ $programme->id }}" 
                                            {{ in_array($programme->id, old('programme_ids', $course->programmes->pluck('id')->toArray())) ? 'selected' : '' }}>
                                            {{ $programme->name }} ({{ $programme->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd key to select multiple programmes</p>
                                @error('programme_ids')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @error('programme_ids.*')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Semester -->
                            <div>
                                <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1">Semester <span class="text-red-500">*</span></label>
                                <select id="semester_id" name="semester_id" required class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="">Select Semester</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}" {{ old('semester_id', $course->semester_id) == $semester->id ? 'selected' : '' }}>
                                            {{ $semester->name }}  ({{ $semester->academicYear->name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>


                                
                                @error('semester_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            

                            
                            <!-- Course Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Course Type <span class="text-red-500">*</span></label>
                                <div class="flex items-center space-x-4">
                                    <div class="flex items-center">
                                        <input id="is_core_yes" name="is_core" type="radio" value="1" {{ old('is_core', $course->is_core) ? 'checked' : '' }} class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300">
                                        <label for="is_core_yes" class="ml-2 block text-sm text-gray-700">Core</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="is_core_no" name="is_core" type="radio" value="0" {{ old('is_core', $course->is_core) ? '' : 'checked' }} class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300">
                                        <label for="is_core_no" class="ml-2 block text-sm text-gray-700">Elective</label>
                                    </div>
                                </div>
                                @error('is_core')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Course Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea id="description" name="description" rows="4" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('description', $course->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="flex items-center justify-end space-x-3 pt-4">
                            <a href="{{ route('courses.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                Update Course
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
