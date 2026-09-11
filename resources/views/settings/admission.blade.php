@extends('components.app-layout')

@section('title', 'Admission Settings')
@section('subtitle', 'Principal name and signature used on final, approved admission letters')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Principal</h2>
        <p class="text-gray-600">This name and signature appear on the final admission letter once an admission has been approved.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
            <p class="text-sm">{{ session('success') }}</p>
        </div>
    @endif

    <form action="{{ route('settings.admission.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="principal_name" class="block text-sm font-medium text-gray-700 mb-1">Principal Name*</label>
                <input type="text" name="principal_name" id="principal_name" value="{{ $settings->where('key', 'principal_name')->first()->value ?? old('principal_name') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                @error('principal_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div></div>

            <div>
                <label for="principal_signature" class="block text-sm font-medium text-gray-700 mb-1">Signature Image</label>
                <input type="file" name="principal_signature" id="principal_signature" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                <p class="mt-1 text-xs text-gray-500">Recommended size: 300x100px. Max 2MB. Formats: JPG, PNG.</p>
                @error('principal_signature')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                @if($settings->where('key', 'principal_signature')->first())
                    <div class="flex flex-col items-center">
                        <p class="text-sm font-medium text-gray-700 mb-2">Current Signature</p>
                        <img src="{{ asset('storage/' . $settings->where('key', 'principal_signature')->first()->value) }}" alt="Principal Signature" class="max-h-24 border border-gray-200 rounded">
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

        <div class="flex justify-end pt-4">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
