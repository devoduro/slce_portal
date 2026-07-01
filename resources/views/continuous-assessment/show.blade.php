<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Continuous Assessment') }} - {{ $course->code }}
            </h2>
            <x-button href="{{ route('continuous-assessment.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Courses') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <p class="text-sm text-gray-500 mb-4">
                        {{ $course->title }} &bull; {{ $semester->name ?? 'No semester' }}
                    </p>

                    @if ($errors->has('scores'))
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded">
                            <ul class="list-disc list-inside text-sm">
                                @foreach ($errors->get('scores') as $errorGroup)
                                    @foreach ((array) $errorGroup as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($rows->isEmpty())
                        <div class="text-center py-8 text-gray-400">
                            No students are registered for this course yet.
                        </div>
                    @else
                        <form method="POST" action="{{ route('continuous-assessment.store', $course) }}">
                            @csrf
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignment</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mid-Semester</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($rows as $row)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">{{ $row['student']->full_name }}</div>
                                                    <div class="text-sm text-gray-500">{{ $row['student']->index_number }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ number_format($row['attendance_score'], 2) }}
                                                    @if($row['setting'])
                                                        <span class="text-xs text-gray-400">/ {{ $row['setting']->attendance_max }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="number" step="0.01" min="0" max="{{ $row['setting']->project_max ?? '' }}"
                                                        name="scores[{{ $row['student']->id }}][project]"
                                                        value="{{ old('scores.' . $row['student']->id . '.project', $row['ca']->project_score ?? '') }}"
                                                        class="w-24 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="number" step="0.01" min="0" max="{{ $row['setting']->assignment_max ?? '' }}"
                                                        name="scores[{{ $row['student']->id }}][assignment]"
                                                        value="{{ old('scores.' . $row['student']->id . '.assignment', $row['ca']->assignment_score ?? '') }}"
                                                        class="w-24 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="number" step="0.01" min="0" max="{{ $row['setting']->mid_semester_max ?? '' }}"
                                                        name="scores[{{ $row['student']->id }}][mid_semester]"
                                                        value="{{ old('scores.' . $row['student']->id . '.mid_semester', $row['ca']->mid_semester_score ?? '') }}"
                                                        class="w-24 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ number_format($row['total'], 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <x-button type="submit">
                                    {{ __('Save CA Scores') }}
                                </x-button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
