@extends('components.app-layout')

@section('title', 'Confirm Bulk Bill')
@section('subtitle', 'Review who this will bill before confirming')

@section('content')
<div class="py-4 max-w-3xl">
    <div class="bg-white rounded-lg shadow-sm p-6 mb-4">
        <p class="text-sm text-gray-700">
            This will add a <strong>{{ \App\Models\FeeCategory::options()[$data['category']] ?? $data['category'] }}</strong> bill item of
            <strong>GH&cent;{{ number_format($data['amount'], 2) }}</strong>
            to <strong>{{ $admissions->count() }}</strong> admission(s) in
            <strong>{{ $programme->name }}</strong> ({{ $academicYear->name }}{{ !empty($data['level']) ? ', Level ' . $data['level'] : '' }}).
        </p>

        @if($admissions->isEmpty())
            <p class="mt-3 text-sm text-yellow-700">No matching admissions found - nothing would be billed.</p>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-4">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Applicant No.</th>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Level</th>
                    <th class="px-4 py-3 text-left">Current Total Billed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($admissions as $admission)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $admission->applicant_number }}</td>
                        <td class="px-4 py-3">{{ $admission->full_name }}</td>
                        <td class="px-4 py-3">{{ $admission->level }}</td>
                        <td class="px-4 py-3">GH&cent;{{ number_format($admission->totalBilled(), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No admissions match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admission-billing.bulk-bill.form') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Back</a>

        @if($admissions->isNotEmpty())
            <form method="POST" action="{{ route('admission-billing.bulk-bill.store') }}" onsubmit="return confirm('Bill {{ $admissions->count() }} admission(s)? This cannot be undone in bulk - items would need removing one at a time.');">
                @csrf
                @foreach($data as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">
                    Confirm &amp; Bill {{ $admissions->count() }}
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
