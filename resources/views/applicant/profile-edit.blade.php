@extends('components.applicant-app-layout')

@section('title', 'My Information')
@section('subtitle', 'Complete and confirm your personal information')

@section('content')
<div class="max-w-3xl py-4">
    @if($admission->profile_confirmed_at)
        <div class="p-4 bg-green-50 border-l-4 border-green-500 text-green-800 rounded mb-4">
            Your information was confirmed on {{ $admission->profile_confirmed_at->format('M j, Y') }} and can no longer be edited.
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('applicant.profile.update') }}" method="POST" class="space-y-6">
            @csrf

            @if($errors->any())
                <div class="p-4 bg-red-50 border border-red-100 rounded-lg">
                    <ul class="list-disc pl-5 space-y-1 text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" value="{{ $admission->full_name }}" disabled class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Programme</label>
                    <input type="text" value="{{ $admission->programme->name ?? '' }}" disabled class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 sm:text-sm">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone*</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $admission->phone) }}" {{ $admission->profile_confirmed_at ? 'disabled' : 'required' }}
                           class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Address*</label>
                    <input type="text" name="address" id="address" value="{{ old('address', $admission->address) }}" {{ $admission->profile_confirmed_at ? 'disabled' : 'required' }}
                           class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label for="hometown" class="block text-sm font-medium text-gray-700">Hometown</label>
                    <input type="text" name="hometown" id="hometown" value="{{ old('hometown', $admission->hometown) }}" {{ $admission->profile_confirmed_at ? 'disabled' : '' }}
                           class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label for="gps_address" class="block text-sm font-medium text-gray-700">GPS Address</label>
                    <input type="text" name="gps_address" id="gps_address" value="{{ old('gps_address', $admission->gps_address) }}" {{ $admission->profile_confirmed_at ? 'disabled' : '' }}
                           class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700">Emergency Contact Name*</label>
                    <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="{{ old('emergency_contact_name', $admission->emergency_contact_name) }}" {{ $admission->profile_confirmed_at ? 'disabled' : 'required' }}
                           class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700">Emergency Contact Phone*</label>
                    <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" value="{{ old('emergency_contact_phone', $admission->emergency_contact_phone) }}" {{ $admission->profile_confirmed_at ? 'disabled' : 'required' }}
                           class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label for="emergency_contact_relationship" class="block text-sm font-medium text-gray-700">Relationship*</label>
                    <input type="text" name="emergency_contact_relationship" id="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $admission->emergency_contact_relationship) }}" {{ $admission->profile_confirmed_at ? 'disabled' : 'required' }}
                           class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
            </div>

            @unless($admission->profile_confirmed_at)
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                        Save Information
                    </button>
                </div>
            @endunless
        </form>

        @unless($admission->profile_confirmed_at)
            <form action="{{ route('applicant.profile.confirm') }}" method="POST" class="mt-4 pt-4 border-t border-gray-100" onsubmit="return confirm('Once confirmed, this information can no longer be edited. Continue?');">
                @csrf
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-primary-600 rounded-md text-sm font-medium text-primary-600 hover:bg-primary-50">
                    <i class="fas fa-lock mr-2"></i> Confirm and Lock My Information
                </button>
            </form>
        @endunless
    </div>
</div>
@endsection
