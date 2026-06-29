@extends('components.app-layout')

@section('title', 'Institution Profile')
@section('subtitle', 'Update institution details, logo, and contact information')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Institution Profile</h2>
        <p class="text-gray-600">Update your institution's details, logo, and contact information.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('settings.institution.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="space-y-4 col-span-1 md:col-span-2">
                <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                
                <div>
                    <label for="institution_name" class="block text-sm font-medium text-gray-700 mb-1">Institution Name*</label>
                    <input type="text" name="institution_name" id="institution_name" value="{{ $settings->where('key', 'institution_name')->first()->value ?? old('institution_name') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                    @error('institution_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="institution_slogan" class="block text-sm font-medium text-gray-700 mb-1">Slogan/Motto</label>
                    <input type="text" name="institution_slogan" id="institution_slogan" value="{{ $settings->where('key', 'institution_slogan')->first()->value ?? old('institution_slogan') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                </div>
            </div>
            
            <!-- Logo -->
            <div class="space-y-4 col-span-1 md:col-span-2">
                <h3 class="text-lg font-medium text-gray-900">Institution Logo</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="institution_logo" class="block text-sm font-medium text-gray-700 mb-1">Upload Logo</label>
                        <input type="file" name="institution_logo" id="institution_logo" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        <p class="mt-1 text-xs text-gray-500">Recommended size: 200x200px. Max 2MB. Formats: JPG, PNG, GIF.</p>
                        @error('institution_logo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        @if($settings->where('key', 'institution_logo')->first())
                            <div class="flex flex-col items-center">
                                <p class="text-sm font-medium text-gray-700 mb-2">Current Logo</p>
                                <img src="{{ asset($settings->where('key', 'institution_logo')->first()->value) }}" alt="Institution Logo" class="max-h-24 border border-gray-200 rounded">
                            </div>
                        @else
                            <div class="flex flex-col items-center">
                                <p class="text-sm font-medium text-gray-700 mb-2">No Logo Uploaded</p>
                                <div class="w-24 h-24 bg-gray-100 rounded flex items-center justify-center">
                                    <i class="fas fa-university text-gray-400 text-3xl"></i>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Academic Affairs Signature -->
            <div class="space-y-4 col-span-1 md:col-span-2">
                <h3 class="text-lg font-medium text-gray-900">Academic Affairs Signature</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="academic_affairs_signature" class="block text-sm font-medium text-gray-700 mb-1">Upload Signature</label>
                        <input type="file" name="academic_affairs_signature" id="academic_affairs_signature" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        <p class="mt-1 text-xs text-gray-500">Recommended size: 300x100px. Max 2MB. Formats: JPG, PNG</p>
                        @error('academic_affairs_signature')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        @if($settings->where('key', 'academic_affairs_signature')->first())
                            <div class="flex flex-col items-center">
                                <p class="text-sm font-medium text-gray-700 mb-2">Current Signature</p>
                                @php
                                    $signature = $settings->where('key', 'academic_affairs_signature')->first();
                                    $path = $signature ? asset('storage/' . $signature->value) : '';
                                @endphp
                              
                                <img src="{{ $path }}" alt="Academic Affairs Signature" class="max-h-24 border border-gray-200 rounded">
                            </div>
                        @else
                            <div class="flex flex-col items-center">
                                <p class="text-sm font-medium text-gray-700 mb-2">No Signature Uploaded</p>
                                <div class="w-48 h-16 bg-gray-100 rounded flex items-center justify-center">
                                    <i class="fas fa-signature text-gray-400 text-3xl"></i>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="space-y-4 col-span-1 md:col-span-2">
                <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
                
                <div>
                    <label for="institution_address" class="block text-sm font-medium text-gray-700 mb-1">Address*</label>
                    <textarea name="institution_address" id="institution_address" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>{{ $settings->where('key', 'institution_address')->first()->value ?? old('institution_address') }}</textarea>
                    @error('institution_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="institution_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number*</label>
                        <input type="text" name="institution_phone" id="institution_phone" value="{{ $settings->where('key', 'institution_phone')->first()->value ?? old('institution_phone') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                        @error('institution_phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="institution_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address*</label>
                        <input type="email" name="institution_email" id="institution_email" value="{{ $settings->where('key', 'institution_email')->first()->value ?? old('institution_email') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                        @error('institution_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="col-span-1 md:col-span-2">
                        <label for="institution_website" class="block text-sm font-medium text-gray-700 mb-1">Website URL</label>
                        <input type="url" name="institution_website" id="institution_website" value="{{ $settings->where('key', 'institution_website')->first()->value ?? old('institution_website') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="https://example.com">
                        @error('institution_website')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
