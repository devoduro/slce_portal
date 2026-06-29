<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6">
                        <a href="{{ route('users.index') }}" class="text-blue-600 hover:text-blue-900">
                            &larr; Back to Users
                        </a>
                    </div>
                    
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">User Information</h3>
                        
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Name</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $user->name }}</p>
                            </div>
                            
                            <div>
                                <p class="text-sm font-medium text-gray-500">Email</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $user->email }}</p>
                            </div>
                            
                            <div>
                                <p class="text-sm font-medium text-gray-500">Role</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $user->role }}</p>
                            </div>
                            
                            <div>
                                <p class="text-sm font-medium text-gray-500">Created At</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('F j, Y, g:i a') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4">
                        <x-button href="{{ route('users.edit', $user) }}" variant="primary" icon="fas fa-edit">
                            {{ __('Edit User') }}
                        </x-button>
                        
                        @if(Auth::user()->role === 'admin')
                        <x-button href="{{ route('users.reset-password', $user) }}" variant="secondary" icon="fas fa-key">
                            {{ __('Reset Password') }}
                        </x-button>
                        @endif
                        
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <x-button variant="outline-danger" type="submit" onclick="return confirm('Are you sure you want to delete this user?')">
                                Delete User
                            </x-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
