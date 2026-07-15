@extends('components.app-layout')

@section('title', 'Edit Academic Year')
@section('subtitle', 'Update academic year details')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Edit Academic Year</h2>
        <p class="text-gray-600">Update the details for {{ $academicYear->name }}.</p>
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

    <form action="{{ route('settings.academic-years.update', $academicYear) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Academic Year Name*</label>
                <input type="text" name="name" id="name" value="{{ old('name', $academicYear->name) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                <p class="mt-1 text-xs text-gray-500">Enter a descriptive name for the academic year.</p>
            </div>

            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Academic Year Code*</label>
                <input type="text" name="code" id="code" value="{{ old('code', $academicYear->code) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                <p class="mt-1 text-xs text-gray-500">Enter a short code for the academic year.</p>
            </div>

            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date*</label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $academicYear->start_date->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
            </div>

            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date*</label>
                <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $academicYear->end_date->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
            </div>

            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center">
                    <input type="checkbox" name="is_current" id="is_current" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" {{ old('is_current', $academicYear->is_current) ? 'checked' : '' }}>
                    <label for="is_current" class="ml-2 block text-sm text-gray-700">Set as current academic year</label>
                </div>
                <p class="mt-1 text-xs text-gray-500">If checked, this will be set as the current academic year. Any previously set current academic year will be updated.</p>
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4">
            <a href="{{ route('settings.academic-years') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Update Academic Year
            </button>
        </div>
    </form>
</div>
@endsection
