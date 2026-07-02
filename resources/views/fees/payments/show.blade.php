<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Fee Ledger') }} - {{ $student->full_name }}
            </h2>
            <x-button href="{{ route('fees.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Fees') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Academic Year Switcher -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('fees.show', $student) }}" class="flex items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                        <select name="academic_year_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" onchange="this.form.submit()">
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ ($academicYear?->id) == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            @if(!$academicYear)
                <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                    No academic years have been created yet.
                </div>
            @else
                <!-- Fee Summary -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-500">Fee Amount</p>
                        <p class="text-xl font-semibold text-gray-900">
                            {{ $feeStructure ? number_format($feeStructure->amount, 2) : 'Not set' }}
                        </p>
                        @if($feeStructure && $feeStructure->level === null)
                            <p class="text-xs text-gray-400">Applies to all levels</p>
                        @elseif($feeStructure)
                            <p class="text-xs text-gray-400">Level {{ $feeStructure->level }}</p>
                        @endif
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-500">Total Paid</p>
                        <p class="text-xl font-semibold text-green-600">{{ number_format($totalPaid, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-500">Balance</p>
                        <p class="text-xl font-semibold text-red-600">{{ number_format($balance, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-500">Percentage Paid</p>
                        <p class="text-xl font-semibold text-gray-900">{{ number_format($percentage, 1) }}%</p>
                    </div>
                </div>

                @if(!$feeStructure)
                    <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                        No fee structure has been declared for this student's programme{{ $student->level ? ' / level' : '' }} for {{ $academicYear->name }}.
                        <a href="{{ route('fee-structures.create') }}" class="underline">Add one</a>.
                    </div>
                @endif

                <!-- Record Payment -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Record a Payment</h3>
                    <form method="POST" action="{{ route('fees.payments.store', $student) }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">

                        <div>
                            <x-input id="amount" name="amount" type="number" label="Amount" min="0.01" step="0.01" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Method <span class="text-red-500">*</span></label>
                            <select name="payment_method" class="w-full rounded-lg shadow-sm border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50" required>
                                <option value="cash">Cash</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <x-input id="payment_date" name="payment_date" type="date" label="Payment Date" :value="date('Y-m-d')" required />
                        </div>
                        <div>
                            <x-input id="reference_number" name="reference_number" type="text" label="Reference No." placeholder="Optional" />
                        </div>
                        <div>
                            <x-button type="submit" fullWidth>
                                {{ __('Record Payment') }}
                            </x-button>
                        </div>
                        @error('amount')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('payment_method')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('payment_date')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </form>
                </div>

                <!-- Payment Ledger -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Payment History - {{ $academicYear->name }}</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($payments as $payment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment->payment_date->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($payment->amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $payment->reference_number ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $payment->recordedBy->name ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <form action="{{ route('fees.payments.destroy', $payment) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Remove this payment record?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">No payments recorded yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Arrears (Previous Years) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Arrears (Previous Years)</h3>
                        <p class="text-sm text-gray-500">Total outstanding: <span class="font-semibold text-red-600">{{ number_format($totalArrears, 2) }}</span></p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Owed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($arrears as $arrear)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $arrear->academicYear->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-medium">{{ number_format($arrear->amount, 2) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $arrear->notes ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form action="{{ route('fees.arrears.destroy', $arrear) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Remove this arrears record?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-400">No arrears recorded for this student.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
