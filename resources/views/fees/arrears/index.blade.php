<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Student Arrears') }}
            </h2>
            <x-button href="{{ route('fees.arrears.upload') }}" icon="fas fa-upload">
                {{ __('Upload Debtors List') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('fees.arrears.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or index number" class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <select name="academic_year_id" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Academic Years</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="status" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Statuses</option>
                                <option value="debtor" {{ request('status') === 'debtor' ? 'selected' : '' }}>Debtors (owe the school)</option>
                                <option value="creditor" {{ request('status') === 'creditor' ? 'selected' : '' }}>Creditors (school owes them)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Uploaded From</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Uploaded To</label>
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
                            @if(request()->hasAny(['search', 'academic_year_id', 'status', 'date_from', 'date_to', 'per_page']))
                                <a href="{{ route('fees.arrears.index') }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-primary-600">
                                    Clear
                                </a>
                            @endif
                        </div>
                    </form>

                    @if($arrears->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-file-invoice text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-500">No arrears records found</h3>
                            <p class="text-gray-400 mt-1">Upload a debtors list to get started</p>
                            <div class="mt-6">
                                <x-button href="{{ route('fees.arrears.upload') }}" icon="fas fa-upload">
                                    {{ __('Upload Debtors List') }}
                                </x-button>
                            </div>
                        </div>
                    @else
                        <!-- Empty form that the scattered bulk-select checkboxes/button below associate with
                             via the form="bulk-delete-form" attribute, so it can sit alongside (not wrap)
                             each row's own independent single-delete form. -->
                        <form id="bulk-delete-form" method="POST" action="{{ route('fees.arrears.bulk-destroy') }}">
                            @csrf
                        </form>

                        <div class="flex items-center justify-between mb-3">
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                Select All
                            </label>
                            <button type="submit" form="bulk-delete-form" id="bulk-delete-btn" disabled
                                class="bg-red-600 text-white rounded-md px-4 py-2 text-sm hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                onclick="return confirm('Delete the selected arrears record(s)? This cannot be undone.')">
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Owed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($arrears as $arrear)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" name="arrear_ids[]" value="{{ $arrear->id }}" form="bulk-delete-form" class="arrear-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $arrear->student->full_name ?? 'Unknown' }}</div>
                                                <div class="text-sm text-gray-500">{{ $arrear->student->index_number ?? '-' }}</div>
                                                <div class="text-xs text-gray-400">Ref: {{ $arrear->student->reference_number ?? 'Not set' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $arrear->academicYear->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $arrear->amount < 0 ? 'text-green-600' : 'text-gray-900' }}">
                                                {{ number_format($arrear->amount, 2) }}
                                                @if($arrear->amount < 0)
                                                    <span class="text-xs font-normal text-green-500">(credit - school owes student)</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $arrear->notes ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $arrear->recordedBy->name ?? 'System' }}<br>
                                                <span class="text-xs text-gray-400">{{ $arrear->created_at->format('M d, Y') }}</span>
                                            </td>
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
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $arrears->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.arrear-checkbox');
        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
        const selectedCount = document.getElementById('selected-count');

        function updateBulkDeleteState() {
            const checked = document.querySelectorAll('.arrear-checkbox:checked').length;
            bulkDeleteBtn.disabled = checked === 0;
            selectedCount.textContent = checked;
        }

        selectAll?.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkDeleteState();
        });

        checkboxes.forEach(cb => cb.addEventListener('change', updateBulkDeleteState));
    </script>
    @endpush
</x-app-layout>
