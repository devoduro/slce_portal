<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('STS Score Sheets') }}
            </h2>
            <x-button href="{{ route('sts-score-settings.create') }}" icon="fas fa-plus">
                {{ __('Add Score Sheet') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 text-sm text-blue-800">
                What supervisors mark STS and Internship students on, level by level. You choose the
                criteria, their labels and the marks each carries — supervisors see exactly these lines
                on their score sheet.
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($levels as $row)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Level {{ $row['level'] }}</h3>
                                @if($row['criteria']->isNotEmpty())
                                    <p class="text-sm text-gray-500">
                                        {{ $row['criteria']->count() }} criteria &middot; marked out of
                                        <span class="font-semibold text-gray-700">{{ number_format($row['total'], 2) }}</span>
                                    </p>
                                @else
                                    <p class="text-sm text-gray-400">No score sheet defined</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-3">
                                @if($row['criteria']->isNotEmpty())
                                    <a href="{{ route('sts-score-settings.edit', $row['level']) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('sts-score-settings.destroy', $row['level']) }}" method="POST" class="inline-block"
                                          onsubmit="return confirm('Remove the entire Level {{ $row['level'] }} score sheet?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Remove">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('sts-score-settings.create', ['level' => $row['level']]) }}"
                                       class="text-sm text-primary-600 hover:text-primary-800">
                                        <i class="fas fa-plus mr-1"></i> Set up
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if($row['criteria']->isNotEmpty())
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criterion</th>
                                        <th class="px-6 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Max Mark</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @foreach($row['criteria'] as $criterion)
                                        <tr>
                                            <td class="px-6 py-2 text-sm text-gray-700">{{ $criterion->label }}</td>
                                            <td class="px-6 py-2 text-sm text-gray-700 text-right">{{ number_format((float) $criterion->max_mark, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td class="px-6 py-2 text-sm font-medium text-gray-700">Total</td>
                                        <td class="px-6 py-2 text-sm font-bold text-gray-900 text-right">{{ number_format($row['total'], 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        @else
                            <div class="px-6 py-8 text-center text-sm text-gray-400">
                                Students at this level cannot be scored until a sheet is defined.
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
