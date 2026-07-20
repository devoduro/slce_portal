<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Partner School') }}
            </h2>
            <x-button href="{{ route('partner-schools.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Partner Schools') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('partner-schools.update', $partnerSchool) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input id="name" name="name" type="text" label="School Name" :value="old('name', $partnerSchool->name)" required autofocus />
                            </div>

                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                                <select id="category" name="category" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Category</option>
                                    @foreach(\App\Models\PartnerSchool::CATEGORY_LABELS as $value => $label)
                                        <option value="{{ $value }}" {{ old('category', $partnerSchool->category) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">Type <span class="text-red-500">*</span></label>
                                <select id="type" name="type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Select Type</option>
                                    @foreach(\App\Models\PartnerSchool::TYPE_LABELS as $value => $label)
                                        <option value="{{ $value }}" {{ old('type', $partnerSchool->type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-400">Whether this school hosts STS or Internship placements.</p>
                            </div>

                            <div class="md:col-span-2">
                                <x-input id="location" name="location" type="text" label="Location" :value="old('location', $partnerSchool->location)" />
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Quota per Level</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                @foreach([100, 200, 300, 400] as $level)
                                    <x-input id="capacity_level_{{ $level }}" name="capacity_level_{{ $level }}" type="number" min="0" label="Level {{ $level }}" :value="old('capacity_level_' . $level, $partnerSchool->{'capacity_level_' . $level})" required />
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Update School') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
