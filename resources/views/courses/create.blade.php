<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isset($course) ? __('Edit Course') : __('Add Course') }}
            </h2>
            <x-button href="{{ route('courses.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Courses') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ isset($course) ? route('courses.update', $course) : route('courses.store') }}">
                        @csrf
                        @if(isset($course))
                            @method('PUT')
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <!-- Course Code -->
                                <div>
                                    <x-input
                                        id="code"
                                        name="code"
                                        type="text"
                                        label="Course Code"
                                        :value="old('code', $course->code ?? '')"
                                        required
                                        autofocus
                                        placeholder="e.g. CS101"
                                    />
                                </div>

                                <!-- Course Name -->
                                <div>
                                    <x-input
                                        id="title"
                                        name="title"
                                        type="text"
                                        label="Course Title"
                                        :value="old('title', $course->title ?? '')"
                                        required
                                        placeholder="e.g. Introduction to Computer Science"
                                    />
                                </div>

                                <!-- Credits -->
                                <div>
                                    <x-input
                                        id="credit_hours"
                                        name="credit_hours"
                                        type="number"
                                        label="Credit Hours"
                                        :value="old('credit_hours', $course->credit_hours ?? '')"
                                        required
                                        min="1"
                                        max="6"
                                        step="1"
                                    />
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <!-- Semester -->
                                <div>
                                    <label for="semester_id" class="block text-sm font-medium text-gray-700">Semester</label>
                                    <select id="semester_id" name="semester_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                        <option value="">Select Semester</option>
                                        @foreach($semesters as $semester)
                                            <option value="{{ $semester->id }}" {{ old('semester_id', $course->semester_id ?? '') == $semester->id ? 'selected' : '' }}>
                                                {{ $semester->name }} ({{ $semester->academicYear->name ?? 'N/A' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('semester_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Programmes -->
                                <div>
                                    <label for="programmes" class="block text-sm font-medium text-gray-700">Programmes</label>
                                    <p class="text-xs text-gray-500 mb-1">Select all programmes that can take this course</p>
                                    <select id="programmes" name="programme_ids[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" multiple required size="5">
                                        @foreach($programmes as $programme)
                                            <option value="{{ $programme->id }}" 
                                                {{ in_array($programme->id, old('programme_ids', isset($course) ? $course->programmes->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
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
                                
                                <!-- Course Type -->
                                <div>
                                    <label for="is_core" class="block text-sm font-medium text-gray-700">Course Type</label>
                                    <select id="is_core" name="is_core" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                        <option value="1" {{ old('is_core', $course->is_core ?? '1') == 1 ? 'selected' : '' }}>Core</option>
                                        <option value="0" {{ old('is_core', $course->is_core ?? '') == 0 ? 'selected' : '' }}>Elective</option>
                                    </select>
                                    @error('is_core')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mt-6">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">{{ old('description', $course->description ?? '') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ isset($course) ? __('Update Course') : __('Create Course') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
