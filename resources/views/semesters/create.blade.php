<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add Semester') }}
            </h2>
            <x-button href="{{ route('semesters.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Semesters') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('semesters.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <!-- Semester Name -->
                                <div>
                                    <x-input
                                        id="name"
                                        name="name"
                                        type="text"
                                        label="Semester Name"
                                        :value="old('name')"
                                        required
                                        autofocus
                                        placeholder="e.g. First Semester"
                                    />
                                </div>

                                <!-- Semester Number -->
                                <div>
                                    <x-input
                                        id="semester_number"
                                        name="semester_number"
                                        type="number"
                                        label="Semester Number"
                                        :value="old('semester_number')"
                                        required
                                        min="1"
                                        placeholder="e.g. 1"
                                    />
                                </div>

                                <!-- Academic Year -->
                                <div>
                                    <label for="academic_year_id" class="block text-sm font-medium text-gray-700">Academic Year</label>
                                    <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
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
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <!-- Start Date -->
                                <div>
                                    <x-input
                                        id="start_date"
                                        name="start_date"
                                        type="date"
                                        label="Start Date"
                                        :value="old('start_date')"
                                        required
                                    />
                                </div>

                                <!-- End Date -->
                                <div>
                                    <x-input
                                        id="end_date"
                                        name="end_date"
                                        type="date"
                                        label="End Date"
                                        :value="old('end_date')"
                                        required
                                    />
                                </div>

                                <!-- Is Current -->
                                <div class="flex items-center">
                                    <input id="is_current" name="is_current" type="checkbox" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" {{ old('is_current') ? 'checked' : '' }}>
                                    <label for="is_current" class="ml-2 block text-sm text-gray-900">
                                        Set as current semester
                                    </label>
                                </div>

                                <!-- Required Payment Percentage -->
                                <div>
                                    <x-input
                                        id="required_payment_percentage"
                                        name="required_payment_percentage"
                                        type="number"
                                        label="Required Fee Payment % (for course registration)"
                                        :value="old('required_payment_percentage')"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        placeholder="e.g. 50"
                                        helper="Minimum percentage of the annual fee a student must have paid before they can register courses this semester. Leave blank for no fee gate."
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Create Semester') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
