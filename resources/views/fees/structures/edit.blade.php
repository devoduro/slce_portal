<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Fee Structure') }}
            </h2>
            <x-button href="{{ route('fee-structures.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Fee Structures') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('fee-structures.update', $feeStructure) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Academic Year -->
                            <div>
                                <label for="academic_year_id" class="block text-sm font-medium text-gray-700">Academic Year <span class="text-red-500">*</span></label>
                                <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Academic Year</option>
                                    @foreach($academicYears as $academicYear)
                                        <option value="{{ $academicYear->id }}" {{ old('academic_year_id', $feeStructure->academic_year_id) == $academicYear->id ? 'selected' : '' }}>
                                            {{ $academicYear->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Programme -->
                            <div>
                                <label for="programme_id" class="block text-sm font-medium text-gray-700">Programme <span class="text-red-500">*</span></label>
                                <select id="programme_id" name="programme_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Programme</option>
                                    @foreach($programmes as $programme)
                                        <option value="{{ $programme->id }}" {{ old('programme_id', $feeStructure->programme_id) == $programme->id ? 'selected' : '' }}>
                                            {{ $programme->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('programme_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Level -->
                            <div>
                                <label for="level" class="block text-sm font-medium text-gray-700">Level</label>
                                <select id="level" name="level" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                    <option value="">All levels</option>
                                    @foreach([100, 200, 300, 400] as $levelOption)
                                        <option value="{{ $levelOption }}" {{ old('level', $feeStructure->level) == $levelOption ? 'selected' : '' }}>
                                            {{ $levelOption }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('level')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Leave as "All levels" if the fee is the same across every level of this programme.</p>
                            </div>

                            <!-- Category -->
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700">Fee Category <span class="text-red-500">*</span></label>
                                <select id="category" name="category" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Category</option>
                                    @foreach(\App\Models\FeeCategory::options() as $value => $label)
                                        <option value="{{ $value }}" {{ old('category', $feeStructure->category) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Amount -->
                            <div>
                                <x-input
                                    id="amount"
                                    name="amount"
                                    type="number"
                                    label="Annual Fee Amount"
                                    :value="old('amount', $feeStructure->amount)"
                                    required
                                    min="0.01"
                                    step="0.01"
                                    placeholder="e.g. 3500.00"
                                />
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Update Fee Structure') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
