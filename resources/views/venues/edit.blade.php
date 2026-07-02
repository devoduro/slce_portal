<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Venue') }}
            </h2>
            <x-button href="{{ route('venues.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Venues') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('venues.update', $venue) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <x-input id="name" name="name" type="text" label="Venue Name" :value="old('name', $venue->name)" required autofocus />
                        <x-input id="location" name="location" type="text" label="Location" :value="old('location', $venue->location)" />
                        <x-input id="capacity" name="capacity" type="number" label="Capacity" :value="old('capacity', $venue->capacity)" min="1" />

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Update Venue') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
