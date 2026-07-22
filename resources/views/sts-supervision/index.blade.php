<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My STS/Internship Students') }}
            </h2>
            @if($hasAnyPlacements)
                <x-button href="{{ route('sts-supervision.letter') }}" variant="secondary" icon="fas fa-print" target="_blank">
                    {{ __('Print Supervisor Letter') }}
                </x-button>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm">{{ session('success') }}</div>
                    @endif

                    <form method="GET" action="{{ route('sts-supervision.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
                        <div class="md:col-span-2">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or index number" class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <select name="type" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Types</option>
                                <option value="sts" {{ request('type') === 'sts' ? 'selected' : '' }}>STS</option>
                                <option value="internship" {{ request('type') === 'internship' ? 'selected' : '' }}>Internship</option>
                            </select>
                        </div>
                        <div>
                            <select name="programme_id" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Programmes</option>
                                @foreach($programmes as $programme)
                                    <option value="{{ $programme->id }}" {{ (string) request('programme_id') === (string) $programme->id ? 'selected' : '' }}>{{ $programme->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="level" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Levels</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level }}" {{ (string) request('level') === (string) $level ? 'selected' : '' }}>Level {{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="sort" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Sort: Name</option>
                                <option value="level" {{ $sort === 'level' ? 'selected' : '' }}>Sort: Level</option>
                                <option value="type" {{ $sort === 'type' ? 'selected' : '' }}>Sort: Type</option>
                                <option value="school" {{ $sort === 'school' ? 'selected' : '' }}>Sort: Partner School</option>
                            </select>
                        </div>
                        <div class="md:col-span-6 flex gap-2">
                            <button type="submit" class="bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                            @if(request()->hasAny(['search', 'type', 'programme_id', 'level', 'sort']))
                                <a href="{{ route('sts-supervision.index') }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-primary-600">
                                    Clear
                                </a>
                            @endif
                        </div>
                    </form>

                    @if($placements->isEmpty())
                        <div class="text-center py-8 text-gray-400">
                            @if(request()->hasAny(['search', 'type', 'programme_id', 'level']))
                                No students match this filter.
                            @else
                                You have no assigned STS/Internship students for the current term.
                            @endif
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partner School</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($placements as $placement)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $placement->student->full_name ?? 'N/A' }}
                                                <div class="text-xs text-gray-400">{{ $placement->student->index_number ?? '' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $placement->level }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($placement->type) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $placement->partnerSchool->name ?? 'Not selected yet' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('sts-supervision.score.edit', $placement) }}" class="text-primary-600 hover:text-primary-900">
                                                    <i class="fas fa-star"></i> Score
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
