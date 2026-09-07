<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Graduates') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-5 border-l-4 border-primary-500">
                    <div class="text-xs uppercase tracking-wider text-gray-500">Total Graduates</div>
                    <div class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($summary['total']) }}</div>
                </div>

                <a href="{{ request()->fullUrlWithQuery(['status' => 'owing', 'page' => null]) }}"
                   class="bg-white shadow-sm rounded-lg p-5 border-l-4 border-red-500 hover:shadow-md transition-shadow">
                    <div class="text-xs uppercase tracking-wider text-gray-500">Graduated But Owing</div>
                    <div class="text-2xl font-bold text-red-600 mt-1">{{ number_format($summary['owing_count']) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ number_format($summary['owing_total'], 2) }} outstanding</div>
                </a>

                <div class="bg-white shadow-sm rounded-lg p-5 border-l-4 border-green-500">
                    <div class="text-xs uppercase tracking-wider text-gray-500">Settled</div>
                    <div class="text-2xl font-bold text-green-600 mt-1">{{ number_format($summary['settled_count']) }}</div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5 border-l-4 border-blue-500">
                    <div class="text-xs uppercase tracking-wider text-gray-500">In Credit</div>
                    <div class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($summary['credit_count']) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ number_format($summary['credit_total'], 2) }} owed to students</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">

                    <!-- Filters -->
                    <form method="GET" action="{{ route('graduates.index') }}" class="flex flex-wrap gap-3 items-end mb-6">
                        <div class="flex-1 min-w-[220px]">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, index number or phone..."
                                   class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Programme</label>
                            <select name="programme_id" class="block w-52 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">All Programmes</option>
                                @foreach($programmes as $programme)
                                    <option value="{{ $programme->id }}" {{ (string) request('programme_id') === (string) $programme->id ? 'selected' : '' }}>
                                        {{ $programme->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Graduating Year</label>
                            <select name="graduated_academic_year_id" class="block w-44 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">All Years</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ (string) request('graduated_academic_year_id') === (string) $year->id ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Balance</label>
                            <select name="status" class="block w-40 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">All</option>
                                <option value="owing" {{ request('status') === 'owing' ? 'selected' : '' }}>Owing</option>
                                <option value="settled" {{ request('status') === 'settled' ? 'selected' : '' }}>Settled</option>
                                <option value="credit" {{ request('status') === 'credit' ? 'selected' : '' }}>In Credit</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Sort</label>
                            <select name="sort" class="block w-40 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">Highest Balance</option>
                                <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Name (A–Z)</option>
                            </select>
                        </div>

                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">
                            <i class="fas fa-filter mr-1"></i> Apply
                        </button>

                        @if(request()->hasAny(['search', 'programme_id', 'graduated_academic_year_id', 'status', 'sort']))
                            <a href="{{ route('graduates.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300">
                                <i class="fas fa-times mr-1"></i> Clear
                            </a>
                        @endif
                    </form>

                    <!-- Actions -->
                    <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                        <p class="text-sm text-gray-500">
                            Showing <span class="font-semibold text-gray-700">{{ number_format($graduates->total()) }}</span> graduate(s)
                            @if(request('status') === 'owing') with an outstanding balance @endif
                        </p>

                        <div class="flex gap-2">
                            <a href="{{ route('graduates.export.excel', request()->query()) }}"
                               class="px-3 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                <i class="fas fa-file-excel mr-1"></i> Excel
                            </a>
                            <a href="{{ route('graduates.export.pdf', request()->query()) }}"
                               class="px-3 py-2 bg-red-600 text-white rounded-md text-sm hover:bg-red-700">
                                <i class="fas fa-file-pdf mr-1"></i> PDF
                            </a>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduated</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($graduates as $row)
                                    @php $student = $row['student']; @endphp
                                    <tr class="{{ $row['status'] === 'owing' ? 'bg-red-50/40' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $student->full_name }}</div>
                                            <div class="text-sm text-gray-500">{{ $student->index_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->programme->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $student->graduatedAcademicYear->name ?? 'N/A' }}
                                            @if($student->level)
                                                <div class="text-xs text-gray-400">Level {{ $student->level }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->phone ?: '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $row['balance'] > 0 ? 'text-red-600' : ($row['balance'] < 0 ? 'text-green-600' : 'text-gray-500') }}">
                                            {{ number_format($row['balance'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($row['status'] === 'owing')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Owing</span>
                                            @elseif($row['status'] === 'credit')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">In Credit</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Settled</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            @can('manage-fees')
                                                <a href="{{ route('fees.show', $student) }}" class="text-primary-600 hover:text-primary-800">
                                                    <i class="fas fa-file-invoice-dollar"></i> Statement
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-400">
                                            No graduates match the current filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $graduates->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
