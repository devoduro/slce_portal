@extends('components.app-layout')

@section('title', 'Import Applicant')
@section('subtitle', 'Create a new admission record')

@section('content')
<div class="py-4 max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('admissions.store') }}" method="POST" class="space-y-4">
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
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Full Name*</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" required class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Programme*</label>
                    <select name="programme_id" required class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                        <option value="">Select programme</option>
                        @foreach($programmes as $programme)
                            <option value="{{ $programme->id }}" @selected(old('programme_id') == $programme->id)>{{ $programme->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Academic Year*</label>
                    <select name="academic_year_id" required class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                        <option value="">Select academic year</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Level*</label>
                    <input type="number" name="level" value="{{ old('level', 100) }}" min="100" step="100" required class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Gender*</label>
                    <select name="gender" required class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                        <option value="">Select</option>
                        @foreach(['Male', 'Female', 'Other'] as $g)
                            <option value="{{ $g }}" @selected(old('gender') === $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date of Birth*</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
            </div>

            <p class="text-xs text-gray-500">A login account is created automatically. The applicant's temporary password is shown once, immediately after import.</p>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    Import Applicant
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
