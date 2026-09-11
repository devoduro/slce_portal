@extends('components.applicant-app-layout')

@section('title', 'My Admission')
@section('subtitle', $admission->applicant_number . ' - ' . ($admission->programme->name ?? ''))

@section('content')
<div class="space-y-6 py-4">

    @php
        $statusColors = [
            'not_billed' => 'bg-gray-100 text-gray-700',
            'billed' => 'bg-blue-100 text-blue-700',
            'partially_paid' => 'bg-yellow-100 text-yellow-700',
            'paid_pending_verification' => 'bg-yellow-100 text-yellow-700',
            'confirmed' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700',
            'reversed' => 'bg-red-100 text-red-700',
        ];
        $statusLabels = [
            'not_billed' => 'Not Billed',
            'billed' => 'Billed',
            'partially_paid' => 'Partially Paid',
            'paid_pending_verification' => 'Paid - Pending Verification',
            'confirmed' => 'Confirmed',
            'rejected' => 'Rejected',
            'reversed' => 'Reversed',
        ];
    @endphp

    @if(!$canProceed)
        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 rounded">
            <p class="font-medium">Your admission payment has not yet been confirmed by the Accounts Office.</p>
            <p class="text-sm mt-1">Complete the required payment. Further admission processing will become available after payment confirmation.</p>
        </div>
    @endif

    <div class="p-4 bg-primary-50 border-l-4 border-primary-500 text-primary-800 rounded">
        <p class="text-sm font-medium">Next required action</p>
        <p>{{ $admission->nextAction() }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <p class="text-xs uppercase text-gray-500">Admission Status</p>
            <p class="text-lg font-semibold text-gray-800 mt-1">{{ ucfirst($admission->admission_status) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5">
            <p class="text-xs uppercase text-gray-500">Payment Status</p>
            <span class="inline-block mt-1 px-2 py-1 text-xs font-semibold rounded {{ $statusColors[$admission->payment_status] ?? 'bg-gray-100 text-gray-700' }}">
                {{ $statusLabels[$admission->payment_status] ?? $admission->payment_status }}
            </span>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5">
            <p class="text-xs uppercase text-gray-500">Total Billed</p>
            <p class="text-lg font-semibold text-gray-800 mt-1">GH&cent; {{ number_format($totalBilled, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5">
            <p class="text-xs uppercase text-gray-500">Outstanding Balance</p>
            <p class="text-lg font-semibold {{ $outstanding > 0 ? 'text-red-600' : 'text-green-600' }} mt-1">GH&cent; {{ number_format($outstanding, 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Bill Details</h3>
        @if($admission->billItems->isEmpty())
            <p class="text-sm text-gray-500">No bill has been raised yet. Check back after the Accounts Office bills your admission.</p>
        @else
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 border-b">
                        <th class="py-2">Item</th>
                        <th class="py-2">Description</th>
                        <th class="py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($admission->billItems as $item)
                        <tr class="border-b last:border-0">
                            <td class="py-2">{{ $item->categoryLabel() }}</td>
                            <td class="py-2 text-gray-500">{{ $item->description }}</td>
                            <td class="py-2 text-right">GH&cent; {{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold">
                        <td class="py-2" colspan="2">Total</td>
                        <td class="py-2 text-right">GH&cent; {{ number_format($totalBilled, 2) }}</td>
                    </tr>
                    <tr class="text-green-700">
                        <td class="py-2" colspan="2">Paid</td>
                        <td class="py-2 text-right">GH&cent; {{ number_format($totalPaid, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800">Admission Processing</h3>
            @if(!$canProceed)
                <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-500"><i class="fas fa-lock mr-1"></i>Locked</span>
            @endif
        </div>

        <ul class="space-y-3 text-sm">
            <li class="flex items-center gap-2">
                <i class="fas {{ $admission->profile_confirmed_at ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-300' }}"></i>
                Personal information confirmed
                @if($canProceed && !$admission->profile_confirmed_at)
                    <a href="{{ route('applicant.profile.edit') }}" class="ml-auto text-primary-600 hover:underline">Complete now</a>
                @endif
            </li>
            <li class="flex items-center gap-2">
                <i class="fas {{ $admission->reported_at ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-300' }}"></i>
                Reported to the institution
                @if($canProceed && $admission->profile_confirmed_at && !$admission->reported_at)
                    <form action="{{ route('applicant.report') }}" method="POST" class="ml-auto">
                        @csrf
                        <button type="submit" class="text-primary-600 hover:underline">Mark as reported</button>
                    </form>
                @endif
            </li>
            <li class="flex items-center gap-2">
                <i class="fas {{ $admission->admission_status === 'approved' || $admission->admission_status === 'migrated' ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-300' }}"></i>
                Final admission approved
            </li>
            <li class="flex items-center gap-2">
                <i class="fas {{ $admission->isMigrated() ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-300' }}"></i>
                Migrated to the student register
            </li>
        </ul>
    </div>
</div>
@endsection
