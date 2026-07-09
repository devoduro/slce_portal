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
                                <select id="course_id" name="course_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" data-lecturer-ids="{{ $course->lecturers->pluck('id')->implode(',') }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->code }} - {{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Lecturer</label>
                                <select id="lecturer_id" name="lecturer_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                    <option value="">Not assigned</option>
                                    @foreach($lecturers as $lecturer)
                                        <option value="{{ $lecturer->id }}" {{ old('lecturer_id') == $lecturer->id ? 'selected' : '' }}>{{ $lecturer->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Auto-selected if the course has exactly one assigned lecturer &mdash; if it has more than one, pick which one teaches this specific slot.</p>
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
                                <label for="venue_id" class="block text-sm font-medium text-gray-700">Venue <span id="venue-required-star" class="text-red-500">*</span></label>
                                <select id="venue_id" name="venue_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                    <option value="">Select Venue</option>
                                    @foreach($venues as $venue)
                                        <option value="{{ $venue->id }}" {{ old('venue_id') == $venue->id ? 'selected' : '' }}>{{ $venue->name }}{{ $venue->location ? ' — ' . $venue->location : '' }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">A venue can be shared by up to 2 overlapping classes. No venue you need? <a href="{{ route('venues.create') }}" target="_blank" class="text-primary-600 underline">Add one</a>.</p>
                            </div>

                            <div class="md:col-span-2 flex items-start gap-2 bg-gray-50 rounded-md p-3">
                                <input type="checkbox" id="is_virtual" name="is_virtual" value="1" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" {{ old('is_virtual') ? 'checked' : '' }}>
                                <label for="is_virtual" class="text-sm text-gray-700">
                                    <span class="font-medium">Virtual / Online Class</span>
                                    <p class="text-xs text-gray-500 mt-0.5">Venue becomes optional. Must be scheduled on a weekend (Saturday/Sunday) or in the evening (5:00 PM or later).</p>
                                </label>
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

    @push('scripts')
    <script>
        document.getElementById('course_id').addEventListener('change', function () {
            const lecturerIds = (this.options[this.selectedIndex]?.dataset.lecturerIds || '').split(',').filter(Boolean);
            const lecturerSelect = document.getElementById('lecturer_id');
            // Auto-select only when the course has exactly one lecturer - with several,
            // let the admin explicitly pick which one teaches this specific slot.
            if (lecturerIds.length === 1) {
                lecturerSelect.value = lecturerIds[0];
            }
        });

        document.getElementById('is_virtual').addEventListener('change', function () {
            document.getElementById('venue-required-star').style.display = this.checked ? 'none' : 'inline';
        });
    </script>
    @endpush
</x-app-layout>
