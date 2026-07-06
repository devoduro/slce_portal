<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Promote Students') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 text-sm">
                        <p>Move a whole level of continuing students up to the next level at the start of a new academic year. Their previous class assignment is cleared so that class becomes available for new intake — reassign the promoted students to their new-level classes afterward via <strong>Classes &gt; Assign Students</strong>.</p>
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

                    <form method="GET" action="{{ route('promotions.index') }}" class="flex flex-wrap gap-4 items-end mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Programme</label>
                            <select name="programme_id" class="mt-1 block w-72 pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" onchange="this.form.submit()">
                                <option value="">Select Programme</option>
                                @foreach($programmes as $option)
                                    <option value="{{ $option->id }}" {{ $programme && $programme->id === $option->id ? 'selected' : '' }}>{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>

                    @if($programme)
                        @if(empty($levels))
                            <div class="text-center py-8 text-gray-400">
                                No active students found on this programme at any level.
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Students</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($levels as $row)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Level {{ $row['level'] }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['count'] }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    @if($row['is_terminal'])
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Graduate</span>
                                                    @else
                                                        Level {{ $row['level'] + 100 }}
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <form method="POST" action="{{ route('promotions.preview') }}">
                                                        @csrf
                                                        <input type="hidden" name="programme_id" value="{{ $programme->id }}">
                                                        <input type="hidden" name="level" value="{{ $row['level'] }}">
                                                        <button type="submit" class="bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
                                                            {{ $row['is_terminal'] ? 'Preview Graduation' : 'Preview Promotion' }}
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8 text-gray-400">
                            Select a programme above to see which levels are ready to be promoted.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
