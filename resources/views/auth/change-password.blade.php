<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Change Password') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="max-w-md mx-auto">
                        @if($is_first_login)
                            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
                                <p class="font-bold">First Time Login</p>
                                <p>For security reasons, you must change your password before continuing.</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                            @csrf
                            @method('PUT')

                            <!-- Current Password -->
                            <div>
                                <x-label for="current_password" value="Current Password" />
                                <x-input id="current_password" type="password" name="current_password" required autofocus class="mt-1 block w-full" />
                                @error('current_password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div>
                                <x-label for="password" value="New Password" />
                                <x-input id="password" type="password" name="password" required class="mt-1 block w-full" />
                                <p class="mt-1 text-sm text-gray-600">Password must be at least 8 characters and contain:</p>
                                <ul class="mt-1 text-sm text-gray-600 list-disc list-inside">
                                    <li>At least one uppercase letter</li>
                                    <li>At least one lowercase letter</li>
                                    <li>At least one number</li>
                                    <li>At least one special character</li>
                                </ul>
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <x-label for="password_confirmation" value="Confirm New Password" />
                                <x-input id="password_confirmation" type="password" name="password_confirmation" required class="mt-1 block w-full" />
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <x-button>
                                    Update Password
                                </x-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
