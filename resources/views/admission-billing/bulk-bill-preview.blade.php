@extends('components.app-layout')

@section('title', 'Confirm Bulk Bill')
@section('subtitle', 'Review who this will bill before confirming')

@section('content')
<div class="py-4 max-w-3xl space-y-4">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-700 mb-3">
            This will add the following bill items (totaling <strong>GH&cent;{{ number_format($totalPerAdmission, 2) }}</strong> each) to
            <strong>{{ $admissions->count() }}</strong> admission(s) in
            <strong>{{ $programme->name }}</strong> ({{ $academicYear->name }}{{ !empty($data['level']) ? ', Level ' . $data['level'] : '' }}).
        </p>

        <table class="min-w-full text-sm mb-2">
            <thead class="text-xs uppercase text-gray-500 border-b">
                <tr>
                    <th class="py-2 text-left">Category</th>
                    <th class="py-2 text-left">Description</th>
                    <th class="py-2 text-right">Amount</th>
                    <th class="py-2 text-left">Deadline</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($data['items'] as $item)
                    <tr>
                        <td class="py-2">{{ \App\Models\FeeCategory::options()[$item['category']] ?? $item['category'] }}</td>
                        <td class="py-2 text-gray-500">{{ $item['description'] ?? '-' }}</td>
                        <td class="py-2 text-right">GH&cent;{{ number_format($item['amount'], 2) }}</td>
                        <td class="py-2 text-gray-500">{{ $item['payment_deadline'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($admissions->isEmpty())
            <p class="mt-3 text-sm text-yellow-700">No matching admissions found - nothing would be billed.</p>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
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
            <form method="POST" action="{{ route('admission-billing.bulk-bill.store') }}" onsubmit="return confirm('Bill {{ $admissions->count() }} admission(s) with {{ count($data['items']) }} fee item(s) each? This cannot be undone in bulk - items would need removing one at a time.');">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $data['academic_year_id'] }}">
                <input type="hidden" name="programme_id" value="{{ $data['programme_id'] }}">
                @if(!empty($data['level']))
                    <input type="hidden" name="level" value="{{ $data['level'] }}">
                @endif
                @foreach($data['items'] as $i => $item)
                    <input type="hidden" name="items[{{ $i }}][category]" value="{{ $item['category'] }}">
                    <input type="hidden" name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}">
                    <input type="hidden" name="items[{{ $i }}][amount]" value="{{ $item['amount'] }}">
                    <input type="hidden" name="items[{{ $i }}][payment_deadline]" value="{{ $item['payment_deadline'] ?? '' }}">
                @endforeach
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">
                    Confirm &amp; Bill {{ $admissions->count() }}
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
