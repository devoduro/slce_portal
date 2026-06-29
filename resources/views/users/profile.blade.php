<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row">
                        <!-- Profile Photo and Basic Info -->
                        <div class="md:w-1/3 flex flex-col items-center md:border-r md:pr-6">
                            <div class="w-32 h-32 rounded-full overflow-hidden mb-4">
                                @if($user->profile_photo)
                                    <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-300">
                                        <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">{{ $user->name }}</h3>
                            <p class="text-sm text-gray-500 mb-2">{{ $user->email }}</p>
                            <div class="flex items-center mb-4">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </div>
                        </div>

                        <!-- Profile Details -->
                        <div class="md:w-2/3 md:pl-6 mt-6 md:mt-0">
                            <div class="space-y-6">
                                @if($user->role === 'student' && $user->student)
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-900 mb-2">Student Information</h4>
                                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Index Number</dt>
                                                <dd class="mt-1 text-sm text-gray-900">{{ $user->student->index_number }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Programme</dt>
                                                <dd class="mt-1 text-sm text-gray-900">{{ $user->student->programme->name ?? 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Current CGPA</dt>
                                                <dd class="mt-1 text-sm text-gray-900">{{ number_format($user->student->calculateCGPA(), 2) }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Classification</dt>
                                                <dd class="mt-1 text-sm text-gray-900">{{ $user->student->getClassification() ?? 'N/A' }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                @endif

                                <div class="flex items-center justify-end mt-6">
                                    <x-button href="{{ route('users.edit', $user) }}" icon="fas fa-edit">
                                        {{ __('Edit Profile') }}
                                    </x-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
