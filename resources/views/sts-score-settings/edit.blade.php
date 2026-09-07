<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Level ' . $level . ' Score Sheet') }}
            </h2>
            <x-button href="{{ route('sts-score-settings.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Score Sheets') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 text-sm text-blue-800">
                Rename a criterion or change its maximum mark at any time — marks already entered stay
                attached to it. A criterion that supervisors have already marked against is locked and
                cannot be removed, since deleting it would discard those marks.
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-400 p-4 text-sm text-red-700">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('sts-score-settings.update', $level) }}">
                @csrf
                @method('PUT')

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-1">Scoring Criteria</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            These are the lines supervisors see when scoring a Level {{ $level }} student.
                        </p>

                        @include('sts-score-settings._builder', ['criteria' => $criteria, 'markedIds' => $markedIds])
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <x-button href="{{ route('sts-score-settings.index') }}" variant="secondary">
                            {{ __('Cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('Save Changes') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
