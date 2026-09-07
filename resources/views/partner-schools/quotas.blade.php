<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Partner School Quotas by Term') }}
            </h2>
            <x-button href="{{ route('partner-schools.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Schools') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 text-sm text-blue-800">
                Each term holds its own allocation per school and level, so a batch's places move up with
                them as they are promoted — last term's Level 300 agreement becomes this term's Level 400 —
                while the previous term's record stays exactly as it was.
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

            <!-- Term picker -->
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('partner-schools.quotas.index') }}" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Term</label>
                        <select name="sts_term_id" class="block w-60 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @foreach($stsTerms as $option)
                                <option value="{{ $option->id }}" {{ $term && $term->id === $option->id ? 'selected' : '' }}>
                                    {{ $option->name }}{{ $option->is_current ? ' (current)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                        <select name="type" class="block w-44 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">All types</option>
                            <option value="sts" {{ request('type') === 'sts' ? 'selected' : '' }}>STS</option>
                            <option value="internship" {{ request('type') === 'internship' ? 'selected' : '' }}>Internship</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="School name..."
                               class="block w-56 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">
                        <i class="fas fa-filter mr-1"></i> Show
                    </button>
                </form>
            </div>

            @if(!$term)
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    No STS terms exist yet. Create one before setting quotas.
                </div>
            @else
                @if(!$hasOwnRows)
                    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 text-sm text-amber-800 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <strong>{{ $term->name }} has no quotas of its own yet.</strong>
                            The figures below are the schools' original capacities, shown so you have something to start from.
                            Saving or carrying forward will record them against this term.
                        </div>

                        @if($previousTerm)
                            <form method="POST" action="{{ route('partner-schools.quotas.carry-forward') }}"
                                  onsubmit="return confirm('Carry {{ $previousTerm->name }} allocations into {{ $term->name }}, each moved up one level?')">
                                @csrf
                                <input type="hidden" name="sts_term_id" value="{{ $term->id }}">
                                <input type="hidden" name="from_sts_term_id" value="{{ $previousTerm->id }}">
                                <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-md text-sm hover:bg-amber-700 whitespace-nowrap">
                                    <i class="fas fa-level-up-alt mr-1"></i> Carry forward from {{ $previousTerm->name }}
                                </button>
                            </form>
                        @endif
                    </div>
                @elseif($previousTerm)
                    <div class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-600">
                        <span>Need to top up from the previous batch? Carrying forward only fills levels this term has no figure for.</span>
                        <form method="POST" action="{{ route('partner-schools.quotas.carry-forward') }}"
                              onsubmit="return confirm('Carry {{ $previousTerm->name }} allocations into {{ $term->name }}, each moved up one level? Figures already set here are left alone.')">
                            @csrf
                            <input type="hidden" name="sts_term_id" value="{{ $term->id }}">
                            <input type="hidden" name="from_sts_term_id" value="{{ $previousTerm->id }}">
                            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 whitespace-nowrap">
                                <i class="fas fa-level-up-alt mr-1"></i> Carry forward from {{ $previousTerm->name }}
                            </button>
                        </form>
                    </div>
                @endif

                <form method="POST" action="{{ route('partner-schools.quotas.store') }}">
                    @csrf
                    <input type="hidden" name="sts_term_id" value="{{ $term->id }}">

                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap justify-between items-center gap-3">
                            <div>
                                <h3 class="text-lg font-medium text-gray-800">{{ $term->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $schools->count() }} school(s) usable this term. Figures in grey under each box are how many slots are already taken.</p>
                            </div>
                            <x-button type="submit" icon="fas fa-save">{{ __('Save Quotas') }}</x-button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">School</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        @foreach(\App\Models\PartnerSchool::QUOTA_LEVELS as $level)
                                            @php $servedBy = \App\Models\PartnerSchool::typesForLevel($level, $term); @endphp
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                                Level {{ $level }}
                                                <div class="mt-1 flex flex-wrap gap-1 normal-case">
                                                    @foreach($servedBy as $servedType)
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $servedType === 'internship' ? 'bg-purple-100 text-purple-800' : 'bg-teal-100 text-teal-800' }}">
                                                            {{ $servedType === 'internship' ? 'Internship' : 'STS' }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($schools as $school)
                                        @php $taken = $placedCounts->get($school->id, collect())->pluck('total', 'level'); @endphp
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $school->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $school->categoryLabel() }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if($school->type)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $school->type === 'internship' ? 'bg-purple-100 text-purple-800' : 'bg-teal-100 text-teal-800' }}">
                                                        {{ $school->typeLabel() }}
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800" title="Set this school's type so only the levels it can take are offered">
                                                        Not set
                                                    </span>
                                                @endif
                                            </td>
                                            @foreach(\App\Models\PartnerSchool::QUOTA_LEVELS as $level)
                                                @php
                                                    $capacity = $capacities[$school->id][$level] ?? 0;
                                                    $placed = (int) ($taken[$level] ?? 0);
                                                    $accepts = $school->acceptsLevel($level, $term);
                                                @endphp
                                                <td class="px-4 py-3">
                                                    @if($accepts)
                                                        <input type="number" min="0" max="9999"
                                                               name="quotas[{{ $school->id }}][{{ $level }}]"
                                                               value="{{ old('quotas.' . $school->id . '.' . $level, $capacity) }}"
                                                               class="block w-20 border rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $placed > $capacity ? 'border-red-400' : 'border-gray-300' }}">
                                                        @if($placed > 0)
                                                            <div class="text-xs mt-0.5 {{ $placed > $capacity ? 'text-red-600' : 'text-gray-400' }}">
                                                                {{ $placed }} taken
                                                            </div>
                                                        @endif
                                                    @else
                                                        {{-- This school's type never places students at this level, so there is
                                                             nothing to allocate here. Posted as 0 so the term's row set stays
                                                             complete rather than falling back to a legacy figure. --}}
                                                        <input type="hidden" name="quotas[{{ $school->id }}][{{ $level }}]" value="0">
                                                        <span class="text-gray-300 text-sm"
                                                              title="{{ $school->typeLabel() }} does not place students at Level {{ $level }}">&mdash;</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">
                                                No schools are usable in this term.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($schools->isNotEmpty())
                            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                                <x-button type="submit" icon="fas fa-save">{{ __('Save Quotas') }}</x-button>
                            </div>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
