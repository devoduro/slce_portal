<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add STS Term') }}
            </h2>
            <x-button href="{{ route('sts-terms.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to STS Terms') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('sts-terms.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input id="name" name="name" type="text" label="Term Name" :value="old('name')" required autofocus placeholder="e.g. 2025/2026 STS Term" />
                            </div>

                            <div>
                                <label for="semester_id" class="block text-sm font-medium text-gray-700">Academic Year / Term (Semester)</label>
                                <select id="semester_id" name="semester_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Semester</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}" {{ old('semester_id') == $semester->id ? 'selected' : '' }}>
                                            {{ $semester->academicYear->name ?? '' }} &mdash; {{ $semester->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('semester_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-400">The linked semester's fee payment threshold gates student access to school selection.</p>
                            </div>

                            <div>
                                <x-input id="proposed_start_date" name="proposed_start_date" type="date" label="Proposed Start Date" :value="old('proposed_start_date')" required />
                            </div>

                            <div>
                                <x-input id="proposed_end_date" name="proposed_end_date" type="date" label="Proposed End Date" :value="old('proposed_end_date')" required />
                            </div>

                            <div>
                                <x-input id="internship_level_cutoff" name="internship_level_cutoff" type="number" step="100" min="100" max="800" label="Internship Level Cutoff" :value="old('internship_level_cutoff', 300)" required helper="Students at or above this level get the Internship letter; below it, the STS letter." />
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Create Term') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
