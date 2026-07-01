<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Register Device') }}
            </h2>
            <x-button href="{{ route('biometric-devices.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Devices') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('biometric-devices.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input id="serial_number" name="serial_number" type="text" label="Serial Number" :value="old('serial_number')" required autofocus placeholder="e.g. CKVQ123456789" />
                        </div>
                        <div>
                            <x-input id="name" name="name" type="text" label="Device Name" :value="old('name')" required placeholder="e.g. Main Gate Scanner" />
                        </div>
                        <div>
                            <x-input id="location" name="location" type="text" label="Location" :value="old('location')" placeholder="e.g. Registry Block" />
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Register Device') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
