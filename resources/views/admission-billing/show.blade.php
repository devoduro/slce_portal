@extends('components.app-layout')

@section('title', $admission->full_name)
@section('subtitle', $admission->applicant_number . ' - Admission Billing')

@section('content')
<div class="py-4 space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <p class="text-xs uppercase text-gray-500">Total Billed</p>
            <p class="text-lg font-semibold">GH&cent; {{ number_format($admission->totalBilled(), 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5">
            <p class="text-xs uppercase text-gray-500">Total Paid</p>
            <p class="text-lg font-semibold text-green-600">GH&cent; {{ number_format($admission->totalPaid(), 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5">
            <p class="text-xs uppercase text-gray-500">Payment Status</p>
            <span class="inline-block mt-1 px-2 py-1 text-xs font-semibold rounded {{ $admission->payment_status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ ucfirst(str_replace('_', ' ', $admission->payment_status)) }}
            </span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h4 class="font-semibold text-gray-700 mb-4">Bill Items</h4>

        <table class="min-w-full text-sm mb-4">
            <thead class="text-xs uppercase text-gray-500 border-b">
                <tr>
                    <th class="py-2 text-left">Category</th>
                    <th class="py-2 text-left">Description</th>
                    <th class="py-2 text-right">Amount</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($admission->billItems as $item)
                    <tr>
                        <td class="py-2">{{ $item->categoryLabel() }}</td>
                        <td class="py-2 text-gray-500">{{ $item->description }}</td>
                        <td class="py-2 text-right">GH&cent; {{ number_format($item->amount, 2) }}</td>
                        <td class="py-2 text-right">
                            <form action="{{ route('admission-billing.items.destroy', $item) }}" method="POST" onsubmit="return confirm('Remove this bill item?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:underline text-xs">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-500">No bill items yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        <form action="{{ route('admission-billing.items.store', $admission) }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-2 items-end border-t border-gray-100 pt-4">
            @csrf
            <div class="md:col-span-1">
                <label class="block text-xs text-gray-500">Category</label>
                <select name="category" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    @foreach($feeCategories as $slug => $name)
                        <option value="{{ $slug }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs text-gray-500">Description</label>
                <input type="text" name="description" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs text-gray-500">Amount*</label>
                <input type="number" name="amount" step="0.01" min="0.01" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs text-gray-500">Payment Deadline</label>
                <input type="date" name="payment_deadline" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="md:col-span-1">
                <button type="submit" class="w-full px-3 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">Add Item</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h4 class="font-semibold text-gray-700 mb-4">Payments</h4>

        <div class="space-y-3 mb-6">
            @forelse($admission->payments as $payment)
                @php
                    $badge = match($payment->status) {
                        'confirmed' => 'bg-green-100 text-green-700',
                        'verified' => 'bg-blue-100 text-blue-700',
                        'rejected', 'reversed' => 'bg-red-100 text-red-700',
                        default => 'bg-yellow-100 text-yellow-700',
                    };
                @endphp
                <div class="border border-gray-100 rounded-lg p-4 flex flex-wrap justify-between items-center gap-2">
                    <div>
                        <p class="font-medium text-gray-800">GH&cent; {{ number_format($payment->amount, 2) }} &middot; {{ $payment->payment_method }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $payment->bank }} {{ $payment->reference_number }} {{ $payment->receipt_number }}
                            &middot; Recorded by {{ $payment->recordedBy->name ?? '-' }} on {{ $payment->created_at->format('M j, Y') }}
                        </p>
                        @if($payment->verified_at)
                            <p class="text-xs text-gray-500">Verified by {{ $payment->verifiedBy->name ?? '-' }} on {{ $payment->verified_at->format('M j, Y') }}</p>
                        @endif
                        @if($payment->confirmed_at)
                            <p class="text-xs text-gray-500">Confirmed by {{ $payment->confirmedBy->name ?? '-' }} on {{ $payment->confirmed_at->format('M j, Y') }}</p>
                        @endif
                        @if($payment->evidence_path)
                            <p class="text-xs"><i class="fas fa-paperclip mr-1"></i>Evidence attached</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 text-xs rounded {{ $badge }}">{{ ucfirst($payment->status) }}</span>

                        @if($payment->status === 'recorded')
                            <form action="{{ route('admission-billing.payments.verify', $payment) }}" method="POST">
                                @csrf @method('PUT')
                                <button type="submit" class="px-2 py-1 text-xs bg-blue-50 text-blue-700 rounded hover:bg-blue-100">Verify</button>
                            </form>
                        @endif

                        @if($payment->status === 'verified')
                            <form action="{{ route('admission-billing.payments.confirm', $payment) }}" method="POST">
                                @csrf @method('PUT')
                                <button type="submit" class="px-2 py-1 text-xs bg-green-50 text-green-700 rounded hover:bg-green-100">Confirm</button>
                            </form>
                        @endif

                        @if(in_array($payment->status, ['recorded', 'verified']))
                            <form action="{{ route('admission-billing.payments.reject', $payment) }}" method="POST" onsubmit="return promptReason(this, 'Reason for rejection:');">
                                @csrf @method('PUT')
                                <input type="hidden" name="reason">
                                <button type="submit" class="px-2 py-1 text-xs bg-red-50 text-red-700 rounded hover:bg-red-100">Reject</button>
                            </form>
                        @endif

                        @if($payment->status === 'confirmed')
                            <form action="{{ route('admission-billing.payments.reverse', $payment) }}" method="POST" onsubmit="return promptReason(this, 'Reason for reversal:');">
                                @csrf @method('PUT')
                                <input type="hidden" name="reason">
                                <button type="submit" class="px-2 py-1 text-xs bg-red-50 text-red-700 rounded hover:bg-red-100">Reverse</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">No payments recorded yet.</p>
            @endforelse
        </div>

        <form action="{{ route('admission-billing.payments.store', $admission) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-3 border-t border-gray-100 pt-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500">Amount*</label>
                <input type="number" name="amount" step="0.01" min="0.01" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500">Payment Method*</label>
                <select name="payment_method" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    <option value="Cash">Cash</option>
                    <option value="Bank Deposit">Bank Deposit</option>
                    <option value="Mobile Money">Mobile Money</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500">Bank / MoMo Provider</label>
                <input type="text" name="bank" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500">Transaction Reference</label>
                <input type="text" name="reference_number" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500">Receipt Number</label>
                <input type="text" name="receipt_number" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500">Evidence (receipt scan)</label>
                <input type="file" name="evidence" accept="image/*,.pdf" class="mt-1 block w-full text-xs">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-500">Remarks</label>
                <input type="text" name="remarks" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">Record Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
    function promptReason(form, message) {
        const reason = window.prompt(message);
        if (!reason) { return false; }
        form.querySelector('input[name="reason"]').value = reason;
        return true;
    }
</script>
@endsection
