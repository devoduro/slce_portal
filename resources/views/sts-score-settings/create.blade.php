<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add STS Score Sheet') }}
            </h2>
            <x-button href="{{ route('sts-score-settings.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Score Sheets') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 text-sm text-blue-800">
                Define what supervisors mark this level on. Each criterion carries its own label and
                maximum mark, and appears as a line on the supervisor's score sheet exactly as you name it here.
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

            @if(empty($availableLevels))
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                    <p class="text-gray-500">Every level already has a score sheet.</p>
                    <div class="mt-4">
                        <x-button href="{{ route('sts-score-settings.index') }}">
                            {{ __('Back to Score Sheets') }}
                        </x-button>
                    </div>
                </div>
            @else
                @if(!empty($usedLevels))
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <form method="GET" action="{{ route('sts-score-settings.create') }}" class="flex flex-wrap gap-3 items-end">
                            <input type="hidden" name="level" value="{{ $level }}">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Start from an existing level's sheet</label>
                                <select name="copy_from" class="block w-64 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <option value="">Start blank</option>
                                    @foreach($usedLevels as $used)
                                        <option value="{{ $used }}" {{ (string) $copyFrom === (string) $used ? 'selected' : '' }}>Copy Level {{ $used }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">
                                <i class="fas fa-copy mr-1"></i> Load
                            </button>
                        </form>
                    </div>
                @endif

                <form method="POST" action="{{ route('sts-score-settings.store') }}">
                    @csrf

                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                        <div class="w-64">
                            <label class="block text-sm font-medium text-gray-700">Level <span class="text-red-500">*</span></label>
                            <select name="level" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 rounded-md">
                                @foreach($availableLevels as $levelOption)
                                    <option value="{{ $levelOption }}" {{ (int) old('level', $level) === $levelOption ? 'selected' : '' }}>
                                        Level {{ $levelOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-1">Scoring Criteria</h3>
                            <p class="text-sm text-gray-500 mb-4">Label each thing supervisors mark on, and how many marks it is worth.</p>

                            @include('sts-score-settings._builder', ['criteria' => $criteria, 'markedIds' => []])
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                            <x-button href="{{ route('sts-score-settings.index') }}" variant="secondary">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Save Score Sheet') }}
                            </x-button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
