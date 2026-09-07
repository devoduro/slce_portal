<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Confirm Promotion') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6 p-4 bg-amber-50 border-l-4 border-amber-400 text-amber-800 text-sm">
                        @if($isTerminal)
                            <p>This will mark <strong>{{ $students->count() }}</strong> student(s) as <strong>graduated</strong> from Level {{ $level }} on the <strong>{{ $programme->name }}</strong> programme. Level {{ $level }} is this programme's final level, so there is no further level to promote them to.</p>
                        @else
                            <p>This will move <strong>{{ $students->count() }}</strong> student(s) from <strong>Level {{ $level }}</strong> to <strong>Level {{ $targetLevel }}</strong> on the <strong>{{ $programme->name }}</strong> programme.</p>
                        @endif
                        <p class="mt-2">Their current class assignment will be cleared so it becomes available for new intake. You'll need to reassign them to a Level {{ $targetLevel ?? $level }} class afterward via <strong>Classes &gt; Assign Students</strong>. Historical results, continuous assessment scores, registrations and arrears are not affected.</p>
                    </div>

                    <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-800 text-sm">
                        <p>For fee purposes this will record that these students studied <strong>{{ $completedYear->name }}</strong> at <strong>Level {{ $level }}</strong>, so that year's bills and balances stay at the Level {{ $level }} rate{{ $isTerminal ? ' and they are shown as graduating at the end of it' : ' even after they move up' }}.</p>
                        <p class="mt-2">If {{ $completedYear->name }} is the year they are <em>about to start</em> rather than the one they are finishing, go back and pick the correct year — recording it against the wrong year re-prices their fees at the wrong level.</p>
                    </div>

                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Index Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Class</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($students as $student)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $student->index_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->full_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->classGroup->name ?? 'Unassigned' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('promotions.index') }}" class="bg-gray-200 text-gray-700 rounded-md px-4 py-2 text-sm hover:bg-gray-300">
                            Cancel
                        </a>
                        <form method="POST" action="{{ route('promotions.store') }}" onsubmit="return confirm('{{ $isTerminal ? 'Mark these students as graduated? This cannot be undone from this screen.' : 'Promote these students to Level ' . $targetLevel . '? This cannot be undone from this screen.' }}')">
                            @csrf
                            <input type="hidden" name="programme_id" value="{{ $programme->id }}">
                            <input type="hidden" name="level" value="{{ $level }}">
                            <input type="hidden" name="completed_academic_year_id" value="{{ $completedYear->id }}">
                            <button type="submit" class="bg-red-600 text-white rounded-md px-4 py-2 text-sm hover:bg-red-700">
                                {{ $isTerminal ? 'Confirm Graduation' : 'Confirm Promotion' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
