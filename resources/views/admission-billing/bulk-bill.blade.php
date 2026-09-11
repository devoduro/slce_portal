@extends('components.app-layout')

@section('title', 'Bulk Bill by Programme')
@section('subtitle', 'Apply one or more bill items to every admission in a programme and academic year at once')

@section('content')
<div class="py-4 max-w-3xl space-y-4">

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-1">Fee Categories</h3>
        <p class="text-sm text-gray-500 mb-3">Shared with the Fees module - add one here if the fee you need to bill isn't listed below yet.</p>

        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($feeCategories as $slug => $name)
                <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">{{ $name }}</span>
            @endforeach
        </div>

        @if(session('success') && str_contains(session('success'), 'added'))
            <div class="mb-3 p-3 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm">{{ session('success') }}</div>
        @endif
        @error('name')
            <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <form action="{{ route('fees.categories.store') }}" method="POST" class="flex items-end gap-2">
            @csrf
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-700 mb-1">New Category Name</label>
                <input type="text" name="name" placeholder="e.g. Mattress Fee" class="w-full rounded-md border-gray-300 text-sm" required>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">
                <i class="fas fa-plus mr-1"></i> Add Category
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-500 mb-6">Use this for a blanket bill everyone in a programme owes (e.g. School Fees + Admission Fee + Mattress Fee, all at once, for every Level 100 B.Ed Upper Primary applicant). For different amounts per applicant, use <a href="{{ route('admission-billing.bill-upload.form') }}" class="text-primary-600 underline">Bulk Bill (spreadsheet)</a> instead.</p>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admission-billing.bulk-bill.preview') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-medium text-gray-700">Fee Items*</label>
                    <button type="button" id="add-item" class="text-sm text-primary-600 hover:underline">
                        <i class="fas fa-plus mr-1"></i> Add another fee
                    </button>
                </div>

                <div id="items-container" class="space-y-3">
                    <div class="fee-item-row grid grid-cols-1 md:grid-cols-12 gap-2 items-end border border-gray-100 rounded-md p-3">
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-500">Category*</label>
                            <select name="items[0][category]" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                @foreach($feeCategories as $slug => $name)
                                    <option value="{{ $slug }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-500">Description</label>
                            <input type="text" name="items[0][description]" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-500">Amount*</label>
                            <input type="number" name="items[0][amount]" step="0.01" min="0.01" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-500">Payment Deadline</label>
                            <input type="date" name="items[0][payment_deadline]" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-1">
                            <button type="button" class="remove-item text-red-500 hover:text-red-700 text-sm" title="Remove">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
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

<script>
    (function () {
        const container = document.getElementById('items-container');
        const addButton = document.getElementById('add-item');
        const categoryOptions = document.querySelector('.fee-item-row select[name^="items"]').innerHTML;
        let itemCount = 1;

        function refreshRemoveButtons() {
            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.disabled = container.querySelectorAll('.fee-item-row').length <= 1;
                btn.classList.toggle('opacity-30', btn.disabled);
            });
        }

        addButton.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'fee-item-row grid grid-cols-1 md:grid-cols-12 gap-2 items-end border border-gray-100 rounded-md p-3';
            row.innerHTML = `
                <div class="md:col-span-3">
                    <label class="block text-xs text-gray-500">Category*</label>
                    <select name="items[${itemCount}][category]" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">${categoryOptions}</select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs text-gray-500">Description</label>
                    <input type="text" name="items[${itemCount}][description]" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500">Amount*</label>
                    <input type="number" name="items[${itemCount}][amount]" step="0.01" min="0.01" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs text-gray-500">Payment Deadline</label>
                    <input type="date" name="items[${itemCount}][payment_deadline]" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="md:col-span-1">
                    <button type="button" class="remove-item text-red-500 hover:text-red-700 text-sm" title="Remove">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
            itemCount++;
            refreshRemoveButtons();
        });

        container.addEventListener('click', function (e) {
            const button = e.target.closest('.remove-item');
            if (button && !button.disabled) {
                button.closest('.fee-item-row').remove();
                refreshRemoveButtons();
            }
        });

        refreshRemoveButtons();
    })();
</script>
@endsection
