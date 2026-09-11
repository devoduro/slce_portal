@extends('components.app-layout')

@section('title', 'Passport Photo')
@section('subtitle', $admission->full_name)

@section('content')
<div class="py-4 max-w-lg">
    <div class="bg-white rounded-lg shadow-sm p-6">
        @if($admission->passport_photo)
            <div class="text-center mb-4">
                <img src="{{ route('admissions.photo.view', $admission) }}" alt="Current photo" class="w-32 h-32 object-cover rounded-lg mx-auto border border-gray-200">
                <p class="text-xs text-gray-500 mt-2">Current photo</p>
            </div>
        @endif

        <form action="{{ route('admissions.photo.update', $admission) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ $admission->passport_photo ? 'Replacement Photo*' : 'Passport Photo*' }}</label>
                <input type="file" name="passport_photo" accept="image/jpeg,image/png" required
                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                <p class="mt-1 text-xs text-gray-500">JPEG or PNG, max 2MB.</p>
                @error('passport_photo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if($admission->passport_photo)
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reason for replacement</label>
                    <input type="text" name="reason" class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <a href="{{ route('admissions.show', $admission) }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">Save Photo</button>
            </div>
        </form>
    </div>
</div>
@endsection
