<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Upload Payments') }}
            </h2>
            <x-button href="{{ route('fees.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Fees') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 text-sm">
                        <p class="font-medium mb-2">File format</p>
                        <p>The file must have these columns: <strong>reference_number</strong> (the student's 7-digit bank reference number - not their index number, since that's the number the bank uses to identify the student on their statements), <strong>amount</strong>, <strong>date</strong> (the payment date), <strong>academic_year</strong> (must match an existing academic year, e.g. "2025/2026"), and an optional <strong>bank_reference</strong> column (the specific transaction/teller reference for that payment, if any - different from the student's own reference_number).</p>
                        <p class="mt-2">A student needs a reference number on file before they can appear in this list - see <a href="{{ route('fees.reference-numbers.index') }}" class="underline">Reference Numbers</a> to assign one.</p>
                        <p class="mt-2"><strong>Amount</strong> can be zero (e.g. <code>0</code>, for a zero-value bank entry) or <strong>negative</strong> (e.g. <code>-50.00</code>, for a reversal/refund) as well as a normal positive figure.</p>
                        <p class="mt-2">Each row creates a new payment - re-uploading the same file will record duplicate payments, so only upload a batch once.</p>
                        <a href="{{ route('fees.payments.template') }}" class="inline-flex items-center gap-1 mt-3 text-blue-700 underline">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 text-sm">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('fees.payments.import') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div>
                            <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-1">Payments File <span class="text-red-500">*</span></label>
                            <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50" required>
                            <p class="mt-1 text-sm text-gray-500">Accepted formats: .xlsx, .xls, .csv (max 10MB)</p>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit" icon="fas fa-upload">
                                {{ __('Upload') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recorded Payments</h3>

                    <form method="GET" action="{{ route('fees.payments.upload') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
                        <div class="md:col-span-2">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, index or reference number" class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">From</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">To</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Per Page</label>
                            <select name="per_page" onchange="this.form.submit()" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                @foreach([20, 50, 100, 200, 500] as $option)
                                    <option value="{{ $option }}" {{ (int) request('per_page', 20) === $option ? 'selected' : '' }}>{{ $option }} per page</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2 items-end">
                            <button type="submit" class="flex-1 bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                            @if(request()->hasAny(['search', 'date_from', 'date_to', 'per_page']))
                                <a href="{{ route('fees.payments.upload') }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-primary-600">
                                    Clear
                                </a>
                            @endif
                        </div>
                    </form>

                    @if($payments->isEmpty())
                        <div class="text-center py-8 text-gray-400">No payments recorded yet.</div>
                    @else
                        <!-- Empty form that the scattered bulk-select checkboxes/button below associate with
                             via the form="bulk-delete-form" attribute, so it can sit alongside (not wrap)
                             each row's own independent single-delete form. -->
                        <form id="bulk-delete-form" method="POST" action="{{ route('fees.payments.bulk-destroy') }}">
                            @csrf
                            @method('DELETE')
                        </form>

                        <div class="flex items-center justify-between mb-3">
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                Select All
                            </label>
                            <button type="submit" form="bulk-delete-form" id="bulk-delete-btn" disabled
                                class="bg-red-600 text-white rounded-md px-4 py-2 text-sm hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                onclick="return confirm('Delete the selected payment(s)? This cannot be undone.')">
                                <i class="fas fa-trash mr-1"></i> Delete Selected (<span id="selected-count">0</span>)
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10"></th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank Ref</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" name="payment_ids[]" value="{{ $payment->id }}" form="bulk-delete-form" class="payment-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $payment->student->full_name ?? 'Unknown' }}</div>
                                                <div class="text-sm text-gray-500">{{ $payment->student->index_number ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $payment->academicYear->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($payment->amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Illuminate\Support\Carbon::parse($payment->payment_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $payment->reference_number ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <form action="{{ route('fees.payments.destroy', $payment) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this payment?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $payments->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.payment-checkbox');
        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
        const selectedCount = document.getElementById('selected-count');

        function updateBulkDeleteState() {
            const checked = document.querySelectorAll('.payment-checkbox:checked').length;
            if (bulkDeleteBtn) bulkDeleteBtn.disabled = checked === 0;
            if (selectedCount) selectedCount.textContent = checked;
        }

        selectAll?.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkDeleteState();
        });

        checkboxes.forEach(cb => cb.addEventListener('change', updateBulkDeleteState));
    </script>
    @endpush
</x-app-layout>
