@extends('components.app-layout')

@section('title', 'Bulk Bill by Programme')
@section('subtitle', 'Apply one bill item to every admission in a programme and academic year at once')

@section('content')
<div class="py-4 max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-500 mb-6">Use this for a blanket fee everyone in a programme owes (e.g. School Fees for all Level 100 B.Ed Upper Primary applicants). For different amounts per applicant, use <a href="{{ route('admission-billing.bill-upload.form') }}" class="text-primary-600 underline">Bulk Bill (spreadsheet)</a> instead.</p>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admission-billing.bulk-bill.preview') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <label class="block text-sm font-medium text-gray-700">Programme*</label>
                    <select name="programme_id" required class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                        <option value="">Select programme</option>
                        @foreach($programmes as $programme)
                            <option value="{{ $programme->id }}" @selected(old('programme_id') == $programme->id)>{{ $programme->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Level</label>
                    <input type="number" name="level" min="100" step="100" value="{{ old('level') }}" placeholder="Leave blank for all levels" class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category*</label>
                    <select name="category" required class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                        @foreach($feeCategories as $slug => $name)
                            <option value="{{ $slug }}" @selected(old('category') === $slug)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount*</label>
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Payment Deadline</label>
                    <input type="date" name="payment_deadline" value="{{ old('payment_deadline') }}" class="mt-1 block w-full rounded-md border-gray-300 sm:text-sm">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('admission-billing.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">
                    Preview
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
