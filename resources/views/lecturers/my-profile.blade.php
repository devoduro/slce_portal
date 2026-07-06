<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                        <div>
                            <p class="text-gray-500">Name</p>
                            <p class="font-medium text-gray-900">{{ $lecturer->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Department</p>
                            <p class="font-medium text-gray-900">{{ $lecturer->department->name ?? 'Not set' }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mb-6">Your name and department are managed by the administrator. You can update your photo, phone and email below.</p>

                    <form method="POST" action="{{ route('lecturer.profile.update') }}" class="space-y-6" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="flex items-center gap-4">
                            @if($lecturer->profile_photo)
                                <img src="{{ asset('storage/' . $lecturer->profile_photo) }}" alt="{{ $lecturer->name }}" class="w-20 h-20 rounded-full object-cover">
                            @else
                                <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                                    <i class="fas fa-user text-3xl"></i>
                                </div>
                            @endif
                            <div class="flex-1">
                                <label for="profile_photo" class="block text-sm font-medium text-gray-700">Photo</label>
                                <input id="profile_photo" name="profile_photo" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                @error('profile_photo')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <x-input id="email" name="email" type="email" label="Email" :value="old('email', $lecturer->email)" />
                        <x-input id="phone" name="phone" type="text" label="Phone" :value="old('phone', $lecturer->phone)" />

                        <div class="mt-6 flex justify-end">
                            <x-button type="submit">
                                {{ __('Save Changes') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
