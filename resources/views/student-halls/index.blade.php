<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Student Halls') }}
            </h2>
            <div class="flex gap-2">
                <x-button href="{{ route('student-halls.print', request()->query()) }}" target="_blank" variant="secondary" icon="fas fa-print">
                    {{ __('Print Hall List') }}
                </x-button>
                <x-button href="{{ route('student-halls.upload') }}" icon="fas fa-upload">
                    {{ __('Upload Halls') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Hall Counts -->
            <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                @forelse($hallCounts as $count)
                    <a href="{{ route('student-halls.index', ['hall' => $count->hall]) }}"
                        class="bg-white rounded-2xl shadow-sm border p-4 hover:border-primary-300 transition-colors {{ request('hall') === $count->hall ? 'border-primary-400 ring-1 ring-primary-300' : 'border-gray-100' }}">
                        <p class="text-sm text-gray-500">{{ $count->hall }}</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $count->total }}</p>
                        <p class="text-xs text-gray-400">student{{ $count->total === 1 ? '' : 's' }}</p>
                    </a>
                @empty
                    <div class="col-span-full text-center text-gray-400 py-4">No halls have been assigned yet.</div>
                @endforelse
                @if($unassignedCount > 0)
                    <a href="{{ route('student-halls.index', ['status' => 'unassigned']) }}"
                        class="bg-white rounded-2xl shadow-sm border p-4 hover:border-amber-300 transition-colors {{ request('status') === 'unassigned' ? 'border-amber-400 ring-1 ring-amber-300' : 'border-gray-100' }}">
                        <p class="text-sm text-gray-500">Unassigned</p>
                        <p class="text-2xl font-bold text-amber-600">{{ $unassignedCount }}</p>
                        <p class="text-xs text-gray-400">student{{ $unassignedCount === 1 ? '' : 's' }}</p>
                    </a>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('student-halls.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or index number" class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <select name="hall" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Halls</option>
                                @foreach($hallCounts as $count)
                                    <option value="{{ $count->hall }}" {{ request('hall') === $count->hall ? 'selected' : '' }}>{{ $count->hall }} ({{ $count->total }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="w-full bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                        </div>
                        @if(request()->hasAny(['search', 'hall', 'status']))
                            <div>
                                <a href="{{ route('student-halls.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-600 hover:text-primary-600">
                                    Clear
                                </a>
                            </div>
                        @endif
                    </form>

                    @if($students->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-building text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-500">No students found</h3>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Index Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hall</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($students as $student)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $student->index_number }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->full_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->programme->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($student->hall)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $student->hall }}</span>
                                                @else
                                                    <span class="text-gray-400">Not set</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $students->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
