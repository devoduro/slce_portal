<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Upload') }} {{ ucfirst($type) }} {{ __('Supervisors') }}
            </h2>
            <x-button href="{{ route('sts-placements.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Placements') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 text-sm">
                        <p class="font-medium mb-2">File format</p>
                        <p>The file must have these columns: <strong>index_number</strong> (the student's index number), <strong>supervisor_1</strong>, and <strong>supervisor_2</strong>.</p>
                        <p class="mt-2"><strong>supervisor_2 can be left blank</strong> - a placement is valid with just one supervisor.</p>
                        <p class="mt-2">Supervisor names must match a lecturer's name in <a href="{{ route('lecturers.index') }}" class="underline" target="_blank">Lecturers</a> exactly (not case-sensitive). A row is skipped if a name doesn't match exactly one lecturer.</p>
                        <p class="mt-2">Only matches students who already have an <strong>{{ ucfirst($type) }}</strong> placement in the current term{{ $term ? " ({$term->name})" : '' }} - {{ $type === 'sts' ? 'Internship' : 'STS' }} placements for the same student are left untouched.</p>
                        <a href="{{ route('sts-placements.supervisors.template', ['type' => $type]) }}" class="inline-flex items-center gap-1 mt-3 text-blue-700 underline">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>

                    @if(!$term)
                        <div class="mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 text-sm">
                            No STS term is currently active. Activate one from <a href="{{ route('sts-terms.index') }}" class="underline">STS Terms</a> first.
                        </div>
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

                    <form method="POST" action="{{ route('sts-placements.supervisors.import') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">

                        <div>
                            <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-1">{{ ucfirst($type) }} Supervisors File <span class="text-red-500">*</span></label>
                            <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50" required>
                            <p class="mt-1 text-sm text-gray-500">Accepted formats: .xlsx, .xls, .csv (max 10MB)</p>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit" icon="fas fa-upload" :disabled="!$term">
                                {{ __('Upload') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
