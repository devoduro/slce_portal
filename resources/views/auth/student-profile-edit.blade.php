<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('student.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                        @csrf
                        
                        <!-- Profile Photo -->
                        <div class="flex flex-col items-center space-y-4">
                            <div class="relative group">
                                <div class="w-32 h-32 rounded-full overflow-hidden bg-gray-100">
                                    @if(auth()->user()->student->profile_photo)
                                        <img src="{{ asset('storage/' . auth()->user()->student->profile_photo) }}" 
                                             alt="{{ auth()->user()->name }}" 
                                             class="w-full h-full object-cover"
                                             id="profile-preview">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-300" id="profile-placeholder">
                                            <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                    
                                    <!-- Overlay for hover effect -->
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer rounded-full">
                                        <span class="text-white text-sm">Change Photo</span>
                                    </div>
                                </div>
                                
                                <input type="file" 
                                       name="profile_photo" 
                                       id="profile_photo" 
                                       class="hidden" 
                                       accept="image/*"
                                       onchange="previewImage(this)">
                            </div>
                            
                            @error('profile_photo')
                                <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                            
                            <button type="button" 
                                    onclick="document.getElementById('profile_photo').click()" 
                                    class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                                Upload New Photo
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-6">
                                <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                                
                                <!-- Name -->
                                <div>
                                    <x-label for="name" value="Full Name" />
                                    <x-input type="text" 
                                            name="name" 
                                            id="name" 
                                            value="{{ old('name', auth()->user()->name) }}" 
                                            class="mt-1 block w-full" 
                                            required />
                                    @error('name')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Email -->
                                <div>
                                    <x-label for="email" value="Email Address" />
                                    <x-input type="email" 
                                            name="email" 
                                            id="email" 
                                            value="{{ old('email', auth()->user()->email) }}" 
                                            class="mt-1 block w-full" 
                                            required />
                                    @error('email')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="space-y-6">
                                <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
                                
                                <!-- Phone -->
                                <div>
                                    <x-label for="phone" value="Phone Number" />
                                    <x-input type="tel" 
                                            name="phone" 
                                            id="phone" 
                                            value="{{ old('phone', auth()->user()->student->phone ?? '') }}" 
                                            class="mt-1 block w-full" />
                                    @error('phone')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Address -->
                                <div>
                                    <x-label for="address" value="Address" />
                                    <textarea name="address" 
                                              id="address" 
                                              rows="3" 
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', auth()->user()->student->address ?? '') }}</textarea>
                                    @error('address')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class="space-y-6 border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900">Emergency Contact</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Emergency Contact Name -->
                                <div>
                                    <x-label for="emergency_contact_name" value="Contact Name" />
                                    <x-input type="text" 
                                            name="emergency_contact_name" 
                                            id="emergency_contact_name" 
                                            value="{{ old('emergency_contact_name', auth()->user()->student->emergency_contact_name ?? '') }}" 
                                            class="mt-1 block w-full" />
                                    @error('emergency_contact_name')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Emergency Contact Phone -->
                                <div>
                                    <x-label for="emergency_contact_phone" value="Contact Phone" />
                                    <x-input type="tel" 
                                            name="emergency_contact_phone" 
                                            id="emergency_contact_phone" 
                                            value="{{ old('emergency_contact_phone', auth()->user()->student->emergency_contact_phone ?? '') }}" 
                                            class="mt-1 block w-full" />
                                    @error('emergency_contact_phone')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end space-x-4 border-t pt-6">
                            <a href="{{ route('student.dashboard') }}" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-gray-800 active:bg-gray-50 disabled:opacity-25 transition">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const preview = document.getElementById('profile-preview');
                    const placeholder = document.getElementById('profile-placeholder');
                    
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        // Create new image if it doesn't exist
                        const newImage = document.createElement('img');
                        newImage.src = e.target.result;
                        newImage.id = 'profile-preview';
                        newImage.className = 'w-full h-full object-cover';
                        newImage.alt = 'Profile Photo';
                        
                        if (placeholder) {
                            placeholder.replaceWith(newImage);
                        }
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    @endpush
</x-app-layout>
