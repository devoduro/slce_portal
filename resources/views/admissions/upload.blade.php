@extends('components.app-layout')

@section('title', 'Import Admissions')
@section('subtitle', 'Bulk-import applicants from the institution\'s admission list')

@section('content')
<div class="py-4 max-w-3xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 text-sm">
            <p class="font-medium mb-2">File format</p>
            <p>Expected columns: <strong>Applicant Number</strong> (the institution's own 7-digit number - becomes both the applicant's login id and, later, their student reference/index number), <strong>Title</strong>, <strong>surname</strong>, <strong>othernames</strong>, <strong>sex</strong>, <strong>dob (YYYY/MM/DD)</strong>, <strong>mobile</strong>, <strong>email</strong>, <strong>level</strong>, <strong>program</strong> (must match an existing programme name exactly), <strong>dateofadmission</strong> (year - determines academic year).</p>
            <p class="mt-2">A row already imported (same applicant number) or already belonging to an existing student is skipped, not duplicated.</p>
            <a href="{{ route('admissions.import.template') }}" class="inline-flex items-center gap-1 mt-3 text-blue-700 underline">
                <i class="fas fa-download"></i> Download Template
            </a>
        </div>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admissions.import') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-1">Admission List File*</label>
                <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                <p class="mt-1 text-sm text-gray-500">Accepted formats: .xlsx, .xls, .csv (max 10MB)</p>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admissions.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">
                    <i class="fas fa-upload mr-1"></i> Upload
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
