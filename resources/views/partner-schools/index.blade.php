<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Partner Schools') }}
            </h2>
            <div class="flex gap-2">
                <x-button href="{{ route('partner-schools.print', ['type' => 'sts']) }}" variant="secondary" icon="fas fa-print" target="_blank">
                    {{ __('Print STS Schools') }}
                </x-button>
                <x-button href="{{ route('partner-schools.print', ['type' => 'internship']) }}" variant="secondary" icon="fas fa-print" target="_blank">
                    {{ __('Print Internship Schools') }}
                </x-button>
                <x-button href="{{ route('partner-schools.import.form') }}" variant="secondary" icon="fas fa-upload">
                    {{ __('Import') }}
                </x-button>
                <x-button href="{{ route('partner-schools.create') }}" icon="fas fa-plus">
                    {{ __('Add School') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm">{{ session('success') }}</div>
                    @endif
                    @if(session('warning'))
                        <div class="mb-4 p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-700 text-sm">{{ session('warning') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">{{ session('error') }}</div>
                    @endif

                    <form method="GET" class="flex flex-wrap gap-3 mb-4">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or location..." class="rounded-md border-gray-300 shadow-sm text-sm">
                        <select name="category" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">All Categories</option>
                            @foreach(\App\Models\PartnerSchool::CATEGORY_LABELS as $value => $label)
                                <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="type" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">All Types</option>
                            @foreach(\App\Models\PartnerSchool::TYPE_LABELS as $value => $label)
                                <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="sts_term_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                            @foreach($stsTerms as $stsTerm)
                                <option value="{{ $stsTerm->id }}" {{ (string) $termFilter === (string) $stsTerm->id ? 'selected' : '' }}>
                                    {{ $stsTerm->name }}{{ $stsTerm->is_current ? ' (current)' : '' }}
                                </option>
                            @endforeach
                            <option value="all" {{ $termFilter === 'all' ? 'selected' : '' }}>All Terms</option>
                        </select>
                        <select name="per_page" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm">
                            @foreach([100, 200, 300, 'all'] as $option)
                                <option value="{{ $option }}" {{ request('per_page', 100) == $option ? 'selected' : '' }}>{{ $option === 'all' ? 'All' : $option . ' per page' }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-gray-100 rounded-md text-sm text-gray-700 hover:bg-gray-200">Filter</button>
                        @if(request()->hasAny(['search', 'category', 'type', 'per_page', 'sts_term_id']))
                            <a href="{{ route('partner-schools.index') }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-primary-600">Clear</a>
                        @endif
                    </form>

                    @if($schools->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-school text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-500">No partner schools found</h3>
                        </div>
                    @else
                        <!-- Empty form that the scattered bulk-select checkboxes/button below associate with
                             via the form="bulk-delete-form" attribute, so it can sit alongside (not wrap)
                             each row's own independent single-delete form. -->
                        <form id="bulk-delete-form" method="POST" action="{{ route('partner-schools.bulk-destroy') }}">
                            @csrf
                        </form>

                        <div class="flex items-center justify-between mb-3">
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                Select All
                            </label>
                            <button type="submit" form="bulk-delete-form" id="bulk-delete-btn" disabled
                                class="bg-red-600 text-white rounded-md px-4 py-2 text-sm hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                onclick="return confirm('Delete the selected school(s)? Schools with existing placements will be skipped. This cannot be undone.')">
                                <i class="fas fa-trash mr-1"></i> Delete Selected (<span id="selected-count">0</span>)
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10"></th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quota (100/200/300/400)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Placed / Open (100/200/300/400)
                                            @if($currentTerm)
                                                <div class="normal-case font-normal text-gray-400">{{ $currentTerm->name }}</div>
                                            @endif
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($schools as $school)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" name="school_ids[]" value="{{ $school->id }}" form="bulk-delete-form" class="school-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $school->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{{ $school->categoryLabel() }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($school->stsTerm)
                                                    {{ $school->stsTerm->name }}
                                                @else
                                                    <span class="text-gray-400" title="Available every term">Every term</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($school->type)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $school->type === 'internship' ? 'bg-purple-100 text-purple-800' : 'bg-teal-100 text-teal-800' }}">{{ $school->typeLabel() }}</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800" title="Edit this school to set its type">Not set</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $school->location ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $school->capacity_level_100 }} / {{ $school->capacity_level_200 }} / {{ $school->capacity_level_300 }} / {{ $school->capacity_level_400 }}
                                                @if($school->total_capacity !== null)
                                                    <div class="text-xs text-gray-400">Total: {{ $school->total_capacity }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if(!$currentTerm)
                                                    <span class="text-gray-400">No active term</span>
                                                @else
                                                    @php
                                                        $levelCounts = $placedCounts->get($school->id, collect())->pluck('total', 'level');
                                                        $placed = [];
                                                        $open = [];
                                                        foreach ([100, 200, 300, 400] as $level) {
                                                            $count = (int) ($levelCounts[$level] ?? 0);
                                                            $capacity = (int) $school->{"capacity_level_{$level}"};
                                                            $placed[] = $count;
                                                            $open[] = max(0, $capacity - $count);
                                                        }
                                                    @endphp
                                                    <div>Placed: {{ implode(' / ', $placed) }}</div>
                                                    <div class="text-green-600">Open: {{ implode(' / ', $open) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="{{ route('partner-schools.print', ['partner_school_id' => $school->id]) }}" target="_blank" class="text-gray-600 hover:text-gray-900" title="Print student list">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    <a href="{{ route('partner-schools.edit', $school) }}" class="text-indigo-600 hover:text-indigo-900">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('partner-schools.destroy', $school) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this partner school?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $schools->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.school-checkbox');
        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
        const selectedCount = document.getElementById('selected-count');

        function updateBulkDeleteState() {
            const checked = document.querySelectorAll('.school-checkbox:checked').length;
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
