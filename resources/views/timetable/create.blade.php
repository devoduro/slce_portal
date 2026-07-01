<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add Timetable Entry') }}
            </h2>
            <x-button href="{{ route('timetable.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Timetable') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded">
                            <ul class="list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('timetable.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Class <span class="text-red-500">*</span></label>
                                <select name="class_group_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Class</option>
                                    @foreach($classGroups as $classGroup)
                                        <option value="{{ $classGroup->id }}" {{ old('class_group_id') == $classGroup->id ? 'selected' : '' }}>{{ $classGroup->name }} ({{ $classGroup->programme->name ?? '' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Course <span class="text-red-500">*</span></label>
                                <select name="course_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->code }} - {{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Semester <span class="text-red-500">*</span></label>
                                <select name="semester_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Semester</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}" {{ old('semester_id') == $semester->id ? 'selected' : '' }}>{{ $semester->name }} - {{ $semester->academicYear->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Day of Week <span class="text-red-500">*</span></label>
                                <select name="day_of_week" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Day</option>
                                    @foreach(\App\Http\Controllers\TimetableController::DAYS as $value => $label)
                                        <option value="{{ $value }}" {{ old('day_of_week') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input id="start_time" name="start_time" type="time" label="Start Time" :value="old('start_time')" required />
                            </div>

                            <div>
                                <x-input id="end_time" name="end_time" type="time" label="End Time" :value="old('end_time')" required />
                            </div>

                            <div class="md:col-span-2">
                                <x-input id="venue" name="venue" type="text" label="Venue" :value="old('venue')" required placeholder="e.g. Lecture Hall 1 (must match the biometric device's location for attendance capture)" />
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Create Entry') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
