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
    </div>
</x-app-layout>
