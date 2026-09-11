@extends('components.app-layout')

@section('title', 'Bulk Bill Admissions')
@section('subtitle', 'Bill many admissions at once from a spreadsheet')

@section('content')
<div class="py-4 max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 text-sm">
            <p class="font-medium mb-2">File format</p>
            <p>Columns: <strong>applicant_number</strong> (must match an existing admission), <strong>category</strong> (a Fee Category slug - see the categories already in use on the billing screen), <strong>description</strong> (optional), <strong>amount</strong>, <strong>payment_deadline</strong> (optional, YYYY-MM-DD).</p>
            <p class="mt-2">Each row adds a new bill item - re-uploading the same file bills the same amount again. If a row was wrong, remove the mistaken item on the applicant's billing page rather than re-uploading a "corrected" file.</p>
            <a href="{{ route('admission-billing.bill-upload.template') }}" class="inline-flex items-center gap-1 mt-3 text-blue-700 underline">
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

        <form method="POST" action="{{ route('admission-billing.bill-upload') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-1">Bill List File*</label>
                <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                <p class="mt-1 text-sm text-gray-500">Accepted formats: .xlsx, .xls, .csv (max 10MB)</p>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admission-billing.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">
                    <i class="fas fa-upload mr-1"></i> Upload
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
