<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add STS Score Setting') }}
            </h2>
            <x-button href="{{ route('sts-score-settings.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to STS Score Settings') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('sts-score-settings.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Level <span class="text-red-500">*</span></label>
                                <select name="level" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Level</option>
                                    @foreach([100, 200, 300, 400] as $levelOption)
                                        <option value="{{ $levelOption }}" {{ old('level') == $levelOption ? 'selected' : '' }}>{{ $levelOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div></div>

                            <div>
                                <x-input id="attendance_max" name="attendance_max" type="number" step="0.01" min="0" label="Attendance (Mentor) Max Marks" :value="old('attendance_max')" required />
                            </div>
                            <div>
                                <x-input id="project_max" name="project_max" type="number" step="0.01" min="0" label="Project Max Marks" :value="old('project_max')" required />
                            </div>
                            <div>
                                <x-input id="assignment_max" name="assignment_max" type="number" step="0.01" min="0" label="Assignment Max Marks" :value="old('assignment_max')" required />
                            </div>
                            <div>
                                <x-input id="mid_semester_max" name="mid_semester_max" type="number" step="0.01" min="0" label="Mid-Semester Max Marks" :value="old('mid_semester_max')" required />
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Create Setting') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
