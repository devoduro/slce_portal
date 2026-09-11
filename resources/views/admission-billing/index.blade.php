@extends('components.app-layout')

@section('title', 'Admission Billing')
@section('subtitle', 'Bill applicants and confirm payments')

@section('content')
<div class="py-4 space-y-4">
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or applicant no."
               class="rounded-md border-gray-300 text-sm w-72">
        <select name="payment_status" class="rounded-md border-gray-300 text-sm">
            <option value="">All payment statuses</option>
            @foreach(['not_billed' => 'Not Billed', 'billed' => 'Billed', 'partially_paid' => 'Partially Paid', 'paid_pending_verification' => 'Pending Verification', 'confirmed' => 'Confirmed', 'rejected' => 'Rejected', 'reversed' => 'Reversed'] as $value => $label)
                <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">Filter</button>
    </form>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Applicant No.</th>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Programme</th>
                    <th class="px-4 py-3 text-right">Billed</th>
                    <th class="px-4 py-3 text-right">Paid</th>
                    <th class="px-4 py-3 text-right">Outstanding</th>
                    <th class="px-4 py-3 text-left">Payment Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($admissions as $admission)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $admission->applicant_number }}</td>
                        <td class="px-4 py-3">{{ $admission->full_name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $admission->programme->name ?? '' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($admission->totalBilled(), 2) }}</td>
                        <td class="px-4 py-3 text-right text-green-600">{{ number_format($admission->totalPaid(), 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $admission->outstandingBalance() > 0 ? 'text-red-600' : '' }}">{{ number_format($admission->outstandingBalance(), 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded {{ $admission->payment_status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst(str_replace('_', ' ', $admission->payment_status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admission-billing.show', $admission) }}" class="text-primary-600 hover:underline">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No admissions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $admissions->links() }}
</div>
@endsection
