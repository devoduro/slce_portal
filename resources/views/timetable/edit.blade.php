<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Timetable Entry') }}
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

                    <form method="POST" action="{{ route('timetable.update', $entry) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Class <span class="text-red-500">*</span></label>
                                <select id="class_group_id" name="class_group_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    @foreach($classGroups as $classGroup)
                                        <option value="{{ $classGroup->id }}" data-level="{{ $classGroup->level }}" {{ old('class_group_id', $entry->class_group_id) == $classGroup->id ? 'selected' : '' }}>{{ $classGroup->name }} ({{ $classGroup->programme->name ?? '' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Course <span class="text-red-500">*</span></label>
                                <select id="course_id" name="course_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" data-semester-id="{{ $course->semester_id }}" data-level="{{ $course->level }}" data-lecturer-ids="{{ $course->lecturers->pluck('id')->implode(',') }}" {{ old('course_id', $entry->course_id) == $course->id ? 'selected' : '' }}>{{ $course->code }} - {{ $course->title }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Filtered to courses in the selected semester and the selected class's level.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Lecturer</label>
                                <select id="lecturer_id" name="lecturer_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                    <option value="">Not assigned</option>
                                    @foreach($lecturers as $lecturer)
                                        <option value="{{ $lecturer->id }}" {{ old('lecturer_id', $entry->lecturer_id) == $lecturer->id ? 'selected' : '' }}>{{ $lecturer->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Only shows lecturers assigned to the selected course (auto-selected if there's only one). If none show up, assign a lecturer to the course first via <a href="{{ route('courses.index') }}" target="_blank" class="text-primary-600 underline">Courses</a>.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Semester <span class="text-red-500">*</span></label>
                                <select id="semester_id" name="semester_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}" {{ old('semester_id', $entry->semester_id) == $semester->id ? 'selected' : '' }}>{{ $semester->name }} - {{ $semester->academicYear->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Day of Week <span class="text-red-500">*</span></label>
                                <select name="day_of_week" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    @foreach(\App\Http\Controllers\TimetableController::DAYS as $value => $label)
                                        <option value="{{ $value }}" {{ old('day_of_week', $entry->day_of_week) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input id="start_time" name="start_time" type="time" label="Start Time" :value="old('start_time', substr($entry->start_time, 0, 5))" required />
                            </div>

                            <div>
                                <x-input id="end_time" name="end_time" type="time" label="End Time" :value="old('end_time', substr($entry->end_time, 0, 5))" required />
                            </div>

                            <div class="md:col-span-2">
                                <label for="venue_id" class="block text-sm font-medium text-gray-700">Venue <span id="venue-required-star" class="text-red-500" style="{{ $entry->is_virtual ? 'display:none' : '' }}">*</span></label>
                                <select id="venue_id" name="venue_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                    <option value="">Select Venue</option>
                                    @foreach($venues as $venue)
                                        <option value="{{ $venue->id }}" {{ old('venue_id', $entry->venue_id) == $venue->id ? 'selected' : '' }}>{{ $venue->name }}{{ $venue->location ? ' — ' . $venue->location : '' }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">A venue can be shared by up to 2 overlapping classes. No venue you need? <a href="{{ route('venues.create') }}" target="_blank" class="text-primary-600 underline">Add one</a>.</p>
                            </div>

                            <div class="md:col-span-2 flex items-start gap-2 bg-gray-50 rounded-md p-3">
                                <input type="checkbox" id="is_virtual" name="is_virtual" value="1" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" {{ old('is_virtual', $entry->is_virtual) ? 'checked' : '' }}>
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
                                {{ __('Update Entry') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const classSelect = document.getElementById('class_group_id');
        const semesterSelect = document.getElementById('semester_id');
        const courseSelect = document.getElementById('course_id');
        const lecturerSelect = document.getElementById('lecturer_id');

        function filterCoursesBySemester() {
            const semesterId = semesterSelect.value;
            const selectedClass = classSelect.options[classSelect.selectedIndex];
            const level = selectedClass?.dataset.level || '';

            Array.from(courseSelect.options).forEach(option => {
                if (!option.value) return; // keep the placeholder always visible
                const matchesSemester = !semesterId || option.dataset.semesterId === semesterId;
                const matchesLevel = !level || option.dataset.level === level;
                option.hidden = !(matchesSemester && matchesLevel);
            });

            // If the currently selected course no longer matches, clear it (and the lecturer list with it).
            const selected = courseSelect.options[courseSelect.selectedIndex];
            if (selected && selected.hidden) {
                courseSelect.value = '';
            }

            filterLecturersByCourse();
        }

        function filterLecturersByCourse() {
            const selected = courseSelect.options[courseSelect.selectedIndex];
            const lecturerIds = (selected?.dataset.lecturerIds || '').split(',').filter(Boolean);

            // Strictly filter to only lecturers assigned to the selected course - if the
            // course has none assigned yet, the list stays empty (no "show everyone" fallback).
            Array.from(lecturerSelect.options).forEach(option => {
                if (!option.value) return; // keep "Not assigned" always visible
                option.hidden = !lecturerIds.includes(option.value);
            });

            // Auto-select only when the course has exactly one lecturer - with several,
            // let the admin explicitly pick which one teaches this specific slot.
            if (lecturerIds.length === 1) {
                lecturerSelect.value = lecturerIds[0];
            } else {
                const selectedLecturer = lecturerSelect.options[lecturerSelect.selectedIndex];
                if (selectedLecturer && selectedLecturer.hidden) {
                    lecturerSelect.value = '';
                }
            }
        }

        classSelect.addEventListener('change', filterCoursesBySemester);
        semesterSelect.addEventListener('change', filterCoursesBySemester);
        courseSelect.addEventListener('change', filterLecturersByCourse);

        // Apply filtering immediately so the pre-filled course/lecturer for this entry stay
        // correctly visible/selected, without wiping out its existing values.
        (function initialFilter() {
            const semesterId = semesterSelect.value;
            const selectedClass = classSelect.options[classSelect.selectedIndex];
            const level = selectedClass?.dataset.level || '';
            Array.from(courseSelect.options).forEach(option => {
                if (!option.value) return;
                const matchesSemester = !semesterId || option.dataset.semesterId === semesterId;
                const matchesLevel = !level || option.dataset.level === level;
                option.hidden = !(matchesSemester && matchesLevel);
            });
            filterLecturersByCourse();
        })();

        document.getElementById('is_virtual').addEventListener('change', function () {
            document.getElementById('venue-required-star').style.display = this.checked ? 'none' : 'inline';
        });
    </script>
    @endpush
</x-app-layout>
