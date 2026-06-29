@extends('components.app-layout')

@section('title', 'System Settings')
@section('subtitle', 'Configure general system settings and preferences')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2">System Settings</h2>
        <p class="text-gray-600">Configure general system settings and preferences for the transcript management system.</p>
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

    <form action="{{ route('settings.system.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Institution Information -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Institution Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="institution_name" class="block text-sm font-medium text-gray-700 mb-1">Institution Name</label>
                    <input type="text" name="institution_name" id="institution_name" value="{{ $settings->where('key', 'institution_name')->first()->value ?? old('institution_name') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="institution_code" class="block text-sm font-medium text-gray-700 mb-1">Institution Code</label>
                    <input type="text" name="institution_code" id="institution_code" value="{{ $settings->where('key', 'institution_code')->first()->value ?? old('institution_code') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="institution_address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="institution_address" id="institution_address" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">{{ $settings->where('key', 'institution_address')->first()->value ?? old('institution_address') }}</textarea>
                </div>
                
                <div>
                    <label for="institution_contact" class="block text-sm font-medium text-gray-700 mb-1">Contact Information</label>
                    <textarea name="institution_contact" id="institution_contact" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">{{ $settings->where('key', 'institution_contact')->first()->value ?? old('institution_contact') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Transcript Settings -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Transcript Settings</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="transcript_prefix" class="block text-sm font-medium text-gray-700 mb-1">Transcript Number Prefix</label>
                    <input type="text" name="transcript_prefix" id="transcript_prefix" value="{{ $settings->where('key', 'transcript_prefix')->first()->value ?? old('transcript_prefix') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                    <p class="mt-1 text-xs text-gray-500">Prefix for transcript numbers (e.g., TR-)</p>
                </div>
                
                <div>
                    <label for="transcript_footer" class="block text-sm font-medium text-gray-700 mb-1">Transcript Footer Text</label>
                    <input type="text" name="transcript_footer" id="transcript_footer" value="{{ $settings->where('key', 'transcript_footer')->first()->value ?? old('transcript_footer') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="transcript_signature" class="block text-sm font-medium text-gray-700 mb-1">Signature Title</label>
                    <input type="text" name="transcript_signature" id="transcript_signature" value="{{ $settings->where('key', 'transcript_signature')->first()->value ?? old('transcript_signature') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                    <p class="mt-1 text-xs text-gray-500">Title of the person signing transcripts</p>
                </div>
                
                <div>
                    <label for="transcript_watermark" class="block text-sm font-medium text-gray-700 mb-1">Watermark Text</label>
                    <input type="text" name="transcript_watermark" id="transcript_watermark" value="{{ $settings->where('key', 'transcript_watermark')->first()->value ?? old('transcript_watermark') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                </div>
            </div>
        </div>
        
        <!-- Email Settings -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Email Settings</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="email_from_address" class="block text-sm font-medium text-gray-700 mb-1">From Email Address</label>
                    <input type="email" name="email_from_address" id="email_from_address" value="{{ $settings->where('key', 'email_from_address')->first()->value ?? old('email_from_address') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="email_from_name" class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
                    <input type="text" name="email_from_name" id="email_from_name" value="{{ $settings->where('key', 'email_from_name')->first()->value ?? old('email_from_name') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
