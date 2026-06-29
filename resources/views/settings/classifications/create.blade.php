@extends('components.app-layout')

@section('title', 'Create Classification')
@section('subtitle', 'Add a new degree classification')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Create New Classification</h2>
        <p class="text-gray-600">Define a new degree classification with name and CGPA range.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">Please fix the following errors:</p>
                    <ul class="mt-2 text-sm list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('settings.classifications.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="col-span-1 md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Classification Name*</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. First Class Honors" required>
                <p class="mt-1 text-xs text-gray-500">Enter a name for this classification (e.g. First Class, Second Class Upper, etc.)</p>
            </div>

            <div>
                <label for="min_cgpa" class="block text-sm font-medium text-gray-700 mb-1">Minimum CGPA*</label>
                <input type="number" name="min_cgpa" id="min_cgpa" value="{{ old('min_cgpa') }}" step="0.01" min="0" max="5" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. 3.50" required>
                <p class="mt-1 text-xs text-gray-500">The minimum CGPA required for this classification.</p>
            </div>

            <div>
                <label for="max_cgpa" class="block text-sm font-medium text-gray-700 mb-1">Maximum CGPA*</label>
                <input type="number" name="max_cgpa" id="max_cgpa" value="{{ old('max_cgpa') }}" step="0.01" min="0" max="5" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" placeholder="e.g. 4.00" required>
                <p class="mt-1 text-xs text-gray-500">The maximum CGPA for this classification.</p>
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4">
            <a href="{{ route('settings.classifications') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Create Classification
            </button>
        </div>
    </form>
</div>
@endsection
