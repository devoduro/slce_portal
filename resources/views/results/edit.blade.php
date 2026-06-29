<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Result
            </h2>
            <div class="flex space-x-2">
                <x-button href="{{ route('results.show', $result) }}" variant="secondary" icon="fas fa-eye">
                    View Result
                </x-button>
                <x-button href="{{ route('results.index') }}" variant="secondary" icon="fas fa-arrow-left">
                    Back to List
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('results.update', $result) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Student -->
                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700">Student</label>
                            <select name="student_id" id="student_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ $result->student_id == $student->id ? 'selected' : '' }}>
                                        {{ $student->full_name }} ({{ $student->index_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Course -->
                        <div>
                            <label for="course_id" class="block text-sm font-medium text-gray-700">Course</label>
                            <select name="course_id" id="course_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ $result->course_id == $course->id ? 'selected' : '' }}>
                                        {{ $course->code }} - {{ $course->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Academic Year -->
                        <div>
                            <label for="academic_year_id" class="block text-sm font-medium text-gray-700">Academic Year</label>
                            <select name="academic_year_id" id="academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ $result->academic_year_id == $year->id ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('academic_year_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Semester -->
                        <div>
                            <label for="semester_id" class="block text-sm font-medium text-gray-700">Semester</label>
                            <select name="semester_id" id="semester_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" {{ $result->semester_id == $semester->id ? 'selected' : '' }}>
                                        {{ $semester->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('semester_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Grade -->
                        <div>
                            <label for="grade" class="block text-sm font-medium text-gray-700">Grade</label>
                            <select name="grade" id="grade" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach($gradeSchemes as $scheme)
                                    <option value="{{ $scheme->grade }}" {{ $result->grade == $scheme->grade ? 'selected' : '' }}>
                                        {{ $scheme->grade }} ({{ number_format($scheme->min_score, 1) }}-{{ number_format($scheme->max_score, 1) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('grade')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-3">
                            <x-button href="{{ route('results.index') }}" variant="secondary">
                                Cancel
                            </x-button>
                            <x-button type="submit">
                                Update Result
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
