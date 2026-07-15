<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Fee Ledger') }} - {{ $student->full_name }}
            </h2>
            <div class="flex gap-2">
                <x-button href="{{ route('fees.print', $student) }}" variant="secondary" icon="fas fa-print" target="_blank">
                    {{ __('Print Statement') }}
                </x-button>
                <x-button href="{{ route('fees.index') }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Fees') }}
                </x-button>
            </div>
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

            <!-- Total Balance Due (All Years) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-2 {{ $balanceDue > 0 ? 'border-red-300' : 'border-gray-100' }} p-6">
                <p class="text-sm text-gray-500">Total Balance Due (Arrears + All Years' Unpaid Tuition)</p>
                <p class="text-2xl font-bold {{ $balanceDue > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($balanceDue, 2) }}</p>
                <p class="text-xs text-red-500 mt-1"><strong>Note:</strong> Pay this amount at the Bank. Fees paid are <strong> NON-REFUNDABLE.</strong> - includes arrears and every academic year's unpaid tuition, not just {{ $academicYear->name ?? 'the selected year' }}.</p>
            </div>

            @if(!$academicYear)
                <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                    No academic years have been created yet.
                </div>
            @else
                <!-- Fee Summary -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-500">Previous Balance (Arrears)</p>
                        <p class="text-xl font-semibold {{ $totalArrears > 0 ? 'text-red-600' : ($totalArrears < 0 ? 'text-green-600' : 'text-gray-900') }}">
                            {{ number_format($totalArrears, 2) }}
                        </p>
                        @if($totalArrears < 0)
                            <p class="text-xs text-green-500">Credit - school owes student</p>
                        @endif
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-500">Fee Amount</p>
                        <p class="text-xl font-semibold text-gray-900">
                            {{ $feeStructure || $feeAmount > 0 ? number_format($feeAmount, 2) : 'Not set' }}
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
                    <form method="POST" action="{{ route('fees.payments.store', $student) }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
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
                            <x-input id="bank" name="bank" type="text" label="Bank" placeholder="Optional" />
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

            @endif

            <!-- Full Statement of Account -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Full Statement of Account (All Years)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Mode</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($ledger as $row)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['academic_year'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['description'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600">{{ $row['debit'] ? number_format($row['debit'], 2) : '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600">{{ $row['credit'] ? number_format($row['credit'], 2) : '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">{{ number_format($row['balance'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['bank'] ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['reference_number'] ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['payment_mode'] ? ucwords(str_replace('_', ' ', $row['payment_mode'])) : '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if($row['payment_id'] ?? null)
                                                <div class="flex justify-end gap-2">
                                                    <x-modal :id="'edit-payment-' . $row['payment_id']" title="Edit Payment" maxWidth="lg">
                                                        <x-slot name="trigger">
                                                            <button type="button" class="text-indigo-600 hover:text-indigo-900" title="Edit Payment">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        </x-slot>

                                                        <form method="POST" action="{{ route('fees.payments.update', $row['payment_id']) }}" class="space-y-4">
                                                            @csrf
                                                            @method('PUT')

                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year <span class="text-red-500">*</span></label>
                                                                <select name="academic_year_id" class="w-full rounded-lg shadow-sm border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50" required>
                                                                    @foreach($academicYears as $year)
                                                                        <option value="{{ $year->id }}" {{ $row['academic_year_id'] == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <x-input :id="'amount-' . $row['payment_id']" name="amount" type="number" label="Amount" min="0.01" step="0.01" :value="$row['credit']" required />
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">Method <span class="text-red-500">*</span></label>
                                                                <select name="payment_method" class="w-full rounded-lg shadow-sm border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50" required>
                                                                    @foreach(['cash' => 'Cash', 'mobile_money' => 'Mobile Money', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'other' => 'Other'] as $value => $label)
                                                                        <option value="{{ $value }}" {{ $row['payment_mode'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <x-input :id="'bank-' . $row['payment_id']" name="bank" type="text" label="Bank" placeholder="Optional" :value="$row['bank']" />
                                                            </div>
                                                            <div>
                                                                <x-input :id="'payment_date-' . $row['payment_id']" name="payment_date" type="date" label="Payment Date" :value="\Illuminate\Support\Carbon::parse($row['date'])->format('Y-m-d')" required />
                                                            </div>
                                                            <div>
                                                                <x-input :id="'reference_number-' . $row['payment_id']" name="reference_number" type="text" label="Reference No." placeholder="Optional" :value="$row['reference_number']" />
                                                            </div>

                                                            @if($errors->getBag('edit_payment_' . $row['payment_id'])->any())
                                                                <div class="p-3 bg-red-50 border-l-4 border-red-400 text-red-700 text-sm">
                                                                    <ul class="list-disc list-inside">
                                                                        @foreach($errors->getBag('edit_payment_' . $row['payment_id'])->all() as $error)
                                                                            <li>{{ $error }}</li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            @endif

                                                            <div class="flex justify-end gap-3 pt-2">
                                                                <x-button type="submit">
                                                                    {{ __('Save Changes') }}
                                                                </x-button>
                                                            </div>
                                                        </form>
                                                    </x-modal>

                                                    <form action="{{ route('fees.payments.destroy', $row['payment_id']) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete Payment" onclick="return confirm('Delete this payment record? This cannot be undone.')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-6 py-8 text-center text-gray-400">No fee transactions recorded yet.</td>
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
