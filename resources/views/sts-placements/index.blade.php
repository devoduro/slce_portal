<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('STS/Internship Placements') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">{{ session('error') }}</div>
                    @endif

                    @if(!$term)
                        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 rounded">
                            No STS term is currently active. Activate one from <a href="{{ route('sts-terms.index') }}" class="underline">STS Terms</a> first.
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mb-4">
                            Showing placements for <span class="font-medium">{{ $term->name }}</span>. Assign a supervisor to each student below.
                        </p>

                        <form method="GET" class="flex flex-wrap gap-3 mb-4">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search index number, ref number or name" class="rounded-md border-gray-300 shadow-sm text-sm w-64">
                            <select name="category" class="rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">All Categories</option>
                                @foreach(\App\Models\Programme::STS_CATEGORY_LABELS as $value => $label)
                                    <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select name="type" class="rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">All Types</option>
                                <option value="sts" {{ request('type') === 'sts' ? 'selected' : '' }}>STS</option>
                                <option value="internship" {{ request('type') === 'internship' ? 'selected' : '' }}>Internship</option>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-gray-100 rounded-md text-sm text-gray-700 hover:bg-gray-200">Filter</button>
                            @if(request()->hasAny(['search', 'category', 'type']))
                                <a href="{{ route('sts-placements.index') }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-primary-600">Clear</a>
                            @endif
                        </form>

                        @if($placements->isEmpty())
                            <div class="text-center py-8 text-gray-400">No placements found.</div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partner School</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supervisor</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($placements as $placement)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $placement->student->full_name ?? 'N/A' }}
                                                    <div class="text-xs text-gray-400">{{ $placement->student->index_number ?? '' }} &bull; {{ $placement->student->programme->stsCategoryLabel() ?? '' }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $placement->level }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($placement->type) }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <div class="mb-1">{{ $placement->partnerSchool->name ?? 'Not selected yet' }}</div>
                                                    @php
                                                        $eligibleSchools = $partnerSchools->where('category', $placement->student->programme->sts_category ?? null);
                                                    @endphp
                                                    <div class="flex items-center gap-2">
                                                        <form action="{{ route('sts-placements.change-school', $placement) }}" method="POST" class="flex items-center gap-1">
                                                            @csrf
                                                            @method('PUT')
                                                            <select name="partner_school_id" class="rounded-md border-gray-300 shadow-sm text-xs">
                                                                <option value="">{{ $placement->partner_school_id ? 'Change to...' : 'Assign school...' }}</option>
                                                                @foreach($eligibleSchools as $school)
                                                                    <option value="{{ $school->id }}" {{ $placement->partner_school_id === $school->id ? 'selected' : '' }}>
                                                                        {{ $school->name }} ({{ $school->availableQuota($placement->level, $placement->sts_term_id) }} open)
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <button type="submit" class="text-xs text-primary-600 hover:text-primary-900 underline">Save</button>
                                                        </form>
                                                        @if($placement->partner_school_id)
                                                            <form action="{{ route('sts-placements.undo-school', $placement) }}" method="POST" onsubmit="return confirm('Undo the school selection for {{ $placement->student->full_name }}? This frees their quota slot.')">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="text-xs text-red-600 hover:text-red-900 underline">Undo</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <form action="{{ route('sts-placements.assign-supervisor', $placement) }}" method="POST" class="flex items-center gap-2">
                                                        @csrf
                                                        @method('PUT')
                                                        <select name="lecturer_id" class="rounded-md border-gray-300 shadow-sm text-xs">
                                                            <option value="">Not assigned</option>
                                                            @foreach($lecturers as $lecturer)
                                                                <option value="{{ $lecturer->id }}" {{ $placement->lecturer_id === $lecturer->id ? 'selected' : '' }}>{{ $lecturer->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="text-xs text-primary-600 hover:text-primary-900 underline">Save</button>
                                                    </form>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @php $status = $placement->statusLabel(); @endphp
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $status === 'Ready' ? 'bg-green-100 text-green-800' : ($status === 'School Selected' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                                        {{ $status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
